<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Package;
use App\Models\Payment;
use App\Models\SiteSetting;
use App\Services\ActivityLogger;
use App\Services\ImageCompressor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function create()
    {
        $packages = Package::with(['benefits', 'vendors.category'])->where('status', 'active')->get();
        $client = auth()->user()?->isClient() ? auth()->user() : null;
        $referralSources = SiteSetting::bookingReferralSources();

        $subTypeLabels = [
            'makeup' => 'Makeup Only',
            'akad' => 'Akad',
            'makeup_attire' => 'Makeup & Attire',
            'rumahan' => 'Rumahan',
            'gedung' => 'Gedung',
        ];

        return view('landing.booking', compact('packages', 'subTypeLabels', 'client', 'referralSources'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'package_id' => ['required', 'exists:packages,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email'],
            'instagram' => ['nullable', 'string', 'max:100', 'regex:/^@?[A-Za-z0-9._]+$/'],
            'referral_source' => ['required', Rule::in(SiteSetting::bookingReferralSources())],
            'event_date' => ['required', 'date', 'after_or_equal:today'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'proof' => ['required', 'image', 'max:3072'], // bukti transfer DP1 wajib
        ]);

        $data['code'] = Booking::generateCode();
        $amount = $data['amount'];
        unset($data['amount']);
        $data['package_price'] = Package::findOrFail($data['package_id'])->price;
        $data['email'] = strtolower(trim($data['email']));

        // Guest menunggu verifikasi DP sebelum akun dibuat/ditautkan.
        // User back-office tidak boleh pernah menjadi client sebuah booking.
        $authenticatedClient = auth()->user()?->isClient() ? auth()->user() : null;
        $data['client_id'] = $authenticatedClient?->id;
        $data['created_by'] = auth()->id();
        $data['status'] = Booking::STATUS_PENDING;

        $booking = DB::transaction(function () use ($request, $data, $amount, $authenticatedClient) {
            if ($authenticatedClient) {
                $authenticatedClient->update([
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'instagram' => $data['instagram'] ?? null,
                ]);

                $data['name'] = $authenticatedClient->name;
                $data['phone'] = $authenticatedClient->phone;
                $data['email'] = $authenticatedClient->email;
                $data['instagram'] = $authenticatedClient->instagram;
            }

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
            }
            $booking->payments()->create($paymentData);

            ActivityLogger::log(
                'booking_created',
                'Booking dibuat',
                'Booking '.$booking->code.' untuk '.$booking->name,
                $booking->id,
            );

            return $booking;
        });

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
