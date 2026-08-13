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
    public function users()
    {
        $users = User::withCount('bookings')->orderBy('role')->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'min:6'],
            'role' => ['required', 'in:owner,admin,team,client'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => bcrypt($data['password']),
            'role' => $data['role'],
        ]);

        ActivityLogger::log('user_created', 'User ditambahkan', 'User '.$user->name.' ('.$user->role.') ditambahkan oleh '.auth()->user()->name);

        return back()->with('success', 'User berhasil ditambahkan.');
    }

    public function toggleUser(User $user)
    {
        $user->update(['is_active' => ! $user->is_active]);

        ActivityLogger::log('user_status_changed', 'Status user diubah', 'User '.$user->name.' '.($user->is_active ? 'diaktifkan' : 'dinonaktifkan').' oleh '.auth()->user()->name);

        return back()->with('success', 'Status user diperbarui.');
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
