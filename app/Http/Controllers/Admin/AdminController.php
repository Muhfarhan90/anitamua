<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Reminder;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function staffs()
    {
        $users = User::withCount('bookings')
            ->whereIn('role', [User::ROLE_OWNER, User::ROLE_ADMIN, User::ROLE_TEAM])
            ->where('id', '!=', auth()->id())
            ->orderBy('role')->orderBy('name')
            ->paginate(20);

        return view('admin.users.staff', compact('users'));
    }

    public function clients()
    {
        $users = User::withCount('bookings')
            ->where('role', User::ROLE_CLIENT)
            ->orderBy('name')
            ->paginate(20);

        return view('admin.users.client', compact('users'));
    }

    public function storeStaff(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'min:6'],
            'role' => ['required', 'in:owner,admin,team'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => bcrypt($data['password']),
            'role' => $data['role'],
        ]);

        ActivityLogger::log('user_created', 'Staff ditambahkan', 'Staff '.$user->name.' ('.$user->role.') ditambahkan oleh '.auth()->user()->name);

        return back()->with('success', 'Staff berhasil ditambahkan.');
    }

    public function storeClient(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'min:6'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => bcrypt($data['password']),
            'role' => User::ROLE_CLIENT,
        ]);

        ActivityLogger::log('user_created', 'Klien ditambahkan', 'Akun klien '.$user->name.' ditambahkan oleh '.auth()->user()->name);

        return back()->with('success', 'Akun klien berhasil ditambahkan.');
    }

    public function toggleUser(User $user)
    {
        $this->ensureAdminOnlyManagesClients($user);

        $user->update(['is_active' => ! $user->is_active]);

        ActivityLogger::log('user_status_changed', 'Status user diubah', 'User '.$user->name.' '.($user->is_active ? 'diaktifkan' : 'dinonaktifkan').' oleh '.auth()->user()->name);

        return back()->with('success', 'Status user diperbarui.');
    }

    public function update(User $user, Request $request)
    {
        $this->ensureAdminOnlyManagesClients($user);
        abort_if($user->id === auth()->id(), 403, 'Tidak dapat mengedit akun sendiri.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['nullable', 'in:owner,admin,team,client'],
            'password' => ['nullable', 'min:6'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (auth()->user()->role === User::ROLE_ADMIN) {
            unset($data['role']);
        }

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'] ?? $user->role,
            'is_active' => $request->has('is_active'),
        ]);

        if (! empty($data['password'])) {
            $user->update(['password' => bcrypt($data['password'])]);
        }

        ActivityLogger::log('user_updated', 'User diperbarui', 'User '.$user->name.' diperbarui oleh '.auth()->user()->name);

        return redirect()->back()->with('success', 'User berhasil diperbarui.');
    }

    public function edit(User $user)
    {
        $this->ensureAdminOnlyManagesClients($user);
        abort_if($user->id === auth()->id(), 403, 'Tidak dapat mengedit akun sendiri.');

        return view('admin.users.edit', ['user' => $user]);
    }

    public function destroy(User $user)
    {
        $this->ensureAdminOnlyManagesClients($user);
        abort_if($user->id === auth()->id(), 403, 'Tidak dapat menghapus akun sendiri.');

        $bookingCount = Booking::where('client_id', $user->id)->count();

        if ($bookingCount > 0) {
            return back()->with('warning', "User {$user->name} tidak dapat dihapus karena memiliki {$bookingCount} booking. Nonaktifkan saja akunnya.");
        }

        $name = $user->name;
        $user->delete();

        ActivityLogger::log('user_deleted', 'User dihapus', 'User '.$name.' dihapus oleh '.auth()->user()->name);

        return back()->with('success', 'User '.$name.' berhasil dihapus.');
    }

    private function ensureAdminOnlyManagesClients(User $user): void
    {
        abort_if(
            auth()->user()->role === User::ROLE_ADMIN && $user->role !== User::ROLE_CLIENT,
            403,
            'Admin hanya dapat mengelola akun klien.'
        );
    }

    public function timeline(Request $request)
    {
        $query = ActivityLog::with('user', 'booking')
            ->when($request->booking_id, fn ($q, $id) => $q->where('booking_id', $id))
            ->orderByDesc('created_at');

        $logs = $query->paginate(30)->withQueryString();
        $bookings = Booking::orderByDesc('created_at')->limit(50)->get();

        return view('admin.timeline.index', compact('logs', 'bookings'));
    }

    public function reminders(Request $request)
    {
        $query = Reminder::with('booking')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('scheduled_at');

        $reminders = $query->paginate(20)->withQueryString();
        $bookings = Booking::where('status', Booking::STATUS_BOOKED)->orderBy('event_date')->get();

        return view('admin.reminders.index', compact('reminders', 'bookings'));
    }

    public function storeReminder(Request $request)
    {
        $data = $request->validate([
            'booking_id' => ['required', 'exists:bookings,id'],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string'],
            'scheduled_at' => ['required', 'date'],
            'audience' => ['required', 'in:client,admin,owner'],
            'channel' => ['required', 'in:email,whatsapp,inapp'],
        ]);

        $data['type'] = 'custom';

        $reminder = Reminder::create($data);

        ActivityLogger::log('reminder_created', 'Reminder dibuat', 'Reminder "'.$reminder->title.'" untuk '.$reminder->booking->name.' dijadwalkan '.$reminder->scheduled_at->format('d M Y'), $reminder->booking_id);

        return back()->with('success', 'Reminder berhasil dibuat.');
    }

    public function destroyReminder(Reminder $reminder)
    {
        $reminder->delete();

        return back()->with('success', 'Reminder dihapus.');
    }
}
