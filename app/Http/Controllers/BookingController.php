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
            'instagram' => ['nullable', 'string', 'max:100', 'regex:/^@?[A-Za-z0-9._]+$/'],
            'event_date' => ['required', 'date', 'after_or_equal:today'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:1000'],
            'proof' => ['required', 'image', 'max:3072'], // bukti transfer DP1 wajib
        ]);

        $data['code'] = Booking::generateCode();
        $amount = $data['amount'];
        unset($data['amount']);
        $data['package_price'] = Package::findOrFail($data['package_id'])->price;

        // Akun dibuat otomatis saat DP diverifikasi admin.
        // Jika email sudah punya akun client, booking melekat ke akun tersebut.
        $existingUser = User::where('email', $data['email'])->where('role', User::ROLE_CLIENT)->first();
        if ($existingUser && filled($data['instagram'] ?? null)) {
            $existingUser->update(['instagram' => $data['instagram']]);
        }
        $data['client_id'] = $existingUser->id ?? auth()->id();
        $data['created_by'] = auth()->id() ?? $existingUser->id ?? null;
        $data['status'] = Booking::STATUS_PENDING;

        $booking = Booking::create($data);
        $booking->syncVendorsFromPackage();

        // Nominal DP dari client menjadi nilai awal; admin tetap memverifikasi dan dapat mengoreksinya.
        $paymentData = [
            'type' => 'DP1',
            'amount' => $amount,
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

        $payment = $booking->payments()->oldest('id')->first();

        return view('landing.booking-success', compact('booking', 'payment'));
    }
}
