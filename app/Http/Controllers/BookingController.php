<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Package;
use App\Models\Payment;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ImageCompressor;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function create()
    {
        $packages = Package::with(['benefits', 'vendors.category'])->where('status', 'active')->get();

        $subTypeLabels = [
            'makeup' => 'Makeup Only',
            'akad' => 'Akad',
            'makeup_attire' => 'Makeup & Attire',
            'rumahan' => 'Rumahan',
            'gedung' => 'Gedung',
        ];

        return view('landing.booking', compact('packages', 'subTypeLabels'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'package_id' => ['required', 'exists:packages,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email'],
            'event_date' => ['required', 'date', 'after_or_equal:today'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'proof' => ['required', 'image', 'max:3072'], // bukti transfer DP1 wajib
        ]);

        $year = date('Y');
        $count = Booking::whereYear('created_at', $year)->count() + 1;
        $data['code'] = 'AMU-'.$year.'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);

        // Akun dibuat otomatis saat DP diverifikasi admin.
        // Jika email sudah punya akun client, booking melekat ke akun tersebut.
        $existingUser = User::where('email', $data['email'])->where('role', User::ROLE_CLIENT)->first();
        $data['client_id'] = $existingUser->id ?? auth()->id();
        $data['created_by'] = auth()->id() ?? $existingUser->id ?? null;
        $data['status'] = Booking::STATUS_PENDING;

        $booking = Booking::create($data);

        // Payment DP1 dibuat langsung; nominal diisi admin saat verifikasi.
        $paymentData = [
            'type' => Payment::TYPE_DP1,
            'amount' => 0,
            'due_date' => Carbon::parse($booking->event_date)->subDays(30)->toDateString(),
            'method' => 'transfer',
            'status' => Payment::STATUS_PENDING,
        ];

        if ($request->hasFile('proof')) {
            $paymentData['proof'] = ImageCompressor::compressAndStore($request->file('proof'));
            $paymentData['paid_at'] = now();
        }        $booking->payments()->create($paymentData);

        ActivityLogger::log(
            'booking_created',
            'Booking dibuat',
            'Booking '.$booking->code.' untuk '.$booking->name,
            $booking->id,
        );

        return redirect()
            ->route('booking.success', $booking->code)
            ->with('success', 'Booking berhasil dibuat. Silakan transfer DP1 untuk mengunci jadwal Anda.');
    }

    public function success(string $code)
    {
        $booking = Booking::with('package')->where('code', $code)->firstOrFail();

        $payment = $booking->payments()
            ->whereIn('type', [Payment::TYPE_DP1, 'dp10'])
            ->first();

        return view('landing.booking-success', compact('booking', 'payment'));
    }
}
