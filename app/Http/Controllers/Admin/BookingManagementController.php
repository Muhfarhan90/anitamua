<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Vendor;
use App\Services\ActivityLogger;
use App\Services\ClientAccountService;
use App\Services\ImageCompressor;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookingManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::with(['client', 'package', 'payments'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->q, fn ($q, $s) => $q->where(function ($qq) use ($s) {
                $qq->where('name', 'like', "%{$s}%")
                    ->orWhere('code', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%");
            }))
            ->orderByDesc('created_at');

        $bookings = $query->paginate(15)->withQueryString();

        return view('admin.bookings.index', compact('bookings'));
    }

    public function show(Booking $booking)
    {
        $booking->load([
            'client', 'package', 'payments', 'bookingVendors.vendor.category',
            'schedules.picUser', 'survey', 'fittings', 'packingLists.items.inventoryItem',
            'activityLogs.user', 'packageChangeRequests.oldPackage', 'packageChangeRequests.newPackage',
        ]);
        $packages = Package::where('status', 'active')->get();
        $staff = User::whereIn('role', [User::ROLE_OWNER, User::ROLE_ADMIN, User::ROLE_TEAM])->get();
        $teamMembers = User::where('role', User::ROLE_TEAM)->where('is_active', true)->orderBy('name')->get();

        return view('admin.bookings.show', compact('booking', 'packages', 'staff', 'teamMembers'));
    }

    public function create()
    {
        $packages = Package::where('status', 'active')->get();
        $clients = User::where('role', User::ROLE_CLIENT)->get();

        $subTypeLabels = [
            'makeup' => 'Makeup Only',
            'akad' => 'Akad',
            'makeup_attire' => 'Makeup & Attire',
            'rumahan' => 'Rumahan',
            'gedung' => 'Gedung',
        ];

        return view('admin.bookings.create', compact('packages', 'clients', 'subTypeLabels'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:users,id'],
            'package_id' => ['required', 'exists:packages,id'],
            'event_date' => ['required', 'date'],
            'survey_date' => ['nullable', 'date'],
            'fitting_date' => ['nullable', 'date'],
            'location' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'proof' => ['nullable', 'image', 'max:3072'], // bukti opsional — tanpa bukti pun tetap verified
        ]);

        // Kontak & nama acara diambil dari data klien (klien dibuat dulu sebelum booking)
        $client = User::findOrFail($data['client_id']);
        $data['name'] = $client->name;
        $data['phone'] = $client->phone;
        $data['email'] = $client->email;

        $year = date('Y');
        $count = Booking::whereYear('created_at', $year)->count() + 1;
        $data['code'] = 'AMU-'.$year.'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
        $data['created_by'] = auth()->id();
        $data['status'] = Booking::STATUS_BOOKED; // dibuat admin → langsung sah

        $booking = Booking::create($data);

        ActivityLogger::log('booking_created', 'Booking dibuat oleh Admin', 'Booking '.$booking->code.' untuk '.$booking->name, $booking->id);

        // Tahap DP langsung diverifikasi (dibuat oleh admin — bukti opsional, tanpa bukti pun verified)
        $paymentData = [
            'type' => Payment::TYPE_DP10,
            'amount' => round($booking->package->price * 0.1),
            'due_date' => Carbon::parse($booking->event_date)->subDays(30)->toDateString(),
            'method' => 'transfer',
            'status' => Payment::STATUS_VERIFIED,
            'paid_at' => now(),
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ];

        if ($request->hasFile('proof')) {
            $paymentData['proof'] = ImageCompressor::compressAndStore($request->file('proof'));
        }

        $booking->payments()->create($paymentData);

        ActivityLogger::log('dp_verified', 'DP dikonfirmasi', 'DP 10% dikonfirmasi oleh '.auth()->user()->name.' (booking dibuat via form admin)', $booking->id);

        // Jadwal Hari H otomatis masuk kalender
        $booking->ensureHariHSchedule();

        return redirect()->route('admin.bookings.show', $booking)->with('success', 'Booking berhasil dibuat. DP otomatis terverifikasi, status BOOKED, dan jadwal Hari H masuk kalender.');
    }

    public function verifyDp(Booking $booking, Request $request)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'method' => ['required', 'string'],
        ]);

        $payment = $booking->payments()->where('type', Payment::TYPE_DP10)->first();
        $payment ??= new Payment(['booking_id' => $booking->id, 'type' => Payment::TYPE_DP10]);

        $payment->fill([
            'amount' => $data['amount'],
            'method' => $data['method'],
            'status' => Payment::STATUS_VERIFIED,
            'paid_at' => now(),
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ])->save();

        $booking->update(['status' => Booking::STATUS_BOOKED]);

        $booking->ensureHariHSchedule();

        $this->ensureDp10Payment($booking, $data['amount']);
        $account = app(ClientAccountService::class)->ensure($booking);

        ActivityLogger::log(
            'dp_verified',
            'DP 10% dikonfirmasi',
            'DP 10% sebesar '.number_format($data['amount']).' dikonfirmasi oleh '.auth()->user()->name,
            $booking->id,
            null,
            ['amount' => $data['amount']],
        );

        if ($account) {
            ActivityLogger::log(
                'client_account_created',
                'Akun portal dibuat otomatis',
                'Akun portal client '.$account->email.' dibuat otomatis setelah DP terverifikasi.',
                $booking->id,
            );

            return back()->with('success', 'DP 10% terverifikasi. Booking berstatus BOOKED. Akun dashboard untuk '.$account->email.' dibuat otomatis (password default: '.User::generateDefaultPassword($booking->name).').');
        }

        return back()->with('success', 'DP 10% terverifikasi. Booking berstatus BOOKED.');
    }

    public function cancel(Booking $booking, Request $request)
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $booking->update([
            'status' => Booking::STATUS_CANCELLED,
            'cancelled_reason' => $request->reason,
            'cancelled_at' => now(),
        ]);

        $booking->payments()->where('status', Payment::STATUS_PENDING)->update(['status' => Payment::STATUS_CANCELLED]);

        ActivityLogger::log('booking_cancelled', 'Booking dibatalkan', 'Booking '.$booking->code.' dibatalkan. Alasan: '.$request->reason.'. DP hangus.', $booking->id);

        return back()->with('success', 'Booking dibatalkan. DP dinyatakan hangus.');
    }

    public function changePackage(Booking $booking, Request $request)
    {
        $data = $request->validate([
            'package_id' => ['required', 'exists:packages,id'],
        ]);

        if ((int) $data['package_id'] === (int) $booking->package_id) {
            return back()->with('warning', 'Paket yang dipilih sama dengan paket saat ini.');
        }

        $old = $booking->package;
        $new = Package::findOrFail($data['package_id']);

        $booking->update(['package_id' => $new->id]);

        $this->syncVendorsFromPackage($booking);

        ActivityLogger::log(
            'package_changed',
            'Paket diubah',
            'Paket diubah dari '.$old->name.' ke '.$new->name.' oleh '.auth()->user()->name,
            $booking->id,
            ['package' => $old->name, 'price' => $old->price],
            ['package' => $new->name, 'price' => $new->price],
        );

        return back()->with('success', 'Paket diperbarui ke '.$new->name.'. Vendor disinkronkan dari Master Vendor.');
    }

    public function approvePackageRequest(Booking $booking, Request $request)
    {
        $changeRequest = $booking->packageChangeRequests()
            ->where('status', 'pending')
            ->findOrFail($request->change_request_id);

        $action = $request->input('action');

        if ($action === 'approve') {
            $changeRequest->update([
                'status' => 'approved',
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);

            $old = $booking->package;
            $new = $changeRequest->newPackage;

            $booking->update(['package_id' => $new->id]);
            $this->syncVendorsFromPackage($booking);

            ActivityLogger::log(
                'package_changed',
                'Perubahan paket disetujui',
                'Perubahan paket '.$old->name.' → '.$new->name.' disetujui oleh '.auth()->user()->name,
                $booking->id,
                ['package' => $old->name],
                ['package' => $new->name],
            );

            $message = 'Perubahan paket disetujui.';
        } else {
            $changeRequest->update([
                'status' => 'rejected',
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);

            ActivityLogger::log('package_change_rejected', 'Perubahan paket ditolak', 'Perubahan paket ditolak oleh '.auth()->user()->name, $booking->id);

            $message = 'Perubahan paket ditolak.';
        }

        return back()->with('success', $message);
    }

    public function changeVendor(Booking $booking, Request $request)
    {
        $data = $request->validate([
            'booking_vendor_id' => ['required', 'exists:booking_vendors,id'],
            'vendor_id' => ['required', 'exists:vendors,id'],
        ]);

        $bookingVendor = $booking->bookingVendors()->findOrFail($data['booking_vendor_id']);
        $oldVendor = $bookingVendor->vendor;
        $newVendor = Vendor::findOrFail($data['vendor_id']);

        $bookingVendor->update([
            'vendor_id' => $newVendor->id,
            'price' => $newVendor->price,
            'status' => 'changed',
        ]);

        ActivityLogger::log(
            'vendor_changed',
            'Vendor diganti',
            'Vendor '.$oldVendor->name.' ('.$oldVendor->category->name.') diganti menjadi '.$newVendor->name,
            $booking->id,
            ['vendor' => $oldVendor->name],
            ['vendor' => $newVendor->name],
        );

        return back()->with('success', 'Vendor berhasil diganti.');
    }

    public function syncVendors(Booking $booking)
    {
        $this->syncVendorsFromPackage($booking);

        ActivityLogger::log('vendor_synced', 'Vendor disinkronkan', 'Vendor project '.$booking->code.' disinkronkan dari paket '.$booking->package->name, $booking->id);

        return back()->with('success', 'Vendor disinkronkan dari Master Vendor paket '.$booking->package->name.'.');
    }

    private function syncVendorsFromPackage(Booking $booking): void
    {
        $booking->unsetRelation('package');
        $booking->bookingVendors()->delete();

        foreach ($booking->package->vendors as $vendor) {
            $booking->bookingVendors()->create([
                'vendor_id' => $vendor->id,
                'role' => $vendor->category->name,
                'price' => $vendor->pivot->price,
                'status' => 'confirmed',
            ]);
        }
    }

    /**
     * Pastikan tahap pembayaran pertama (DP) ada.
     * Tahap selanjutnya dibuat manual oleh admin (label & nominal bebas).
     */
    private function ensureDp10Payment(Booking $booking, float $amount): void
    {
        $payment = $booking->payments()->where('type', Payment::TYPE_DP10)->first();

        if (! $payment) {
            $booking->payments()->create([
                'type' => Payment::TYPE_DP10,
                'amount' => $amount,
                'due_date' => Carbon::parse($booking->event_date)->subDays(30)->toDateString(),
                'method' => 'transfer',
                'status' => Payment::STATUS_VERIFIED,
                'paid_at' => now(),
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);
        }
    }

    public function complete(Booking $booking)
    {
        $booking->update(['status' => Booking::STATUS_COMPLETED]);

        ActivityLogger::log('booking_completed', 'Project selesai', 'Project '.$booking->code.' ditandai selesai.', $booking->id);

        return back()->with('success', 'Project ditandai selesai.');
    }
}
