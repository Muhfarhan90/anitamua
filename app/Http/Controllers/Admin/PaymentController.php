<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\ActivityLogger;
use App\Services\ClientAccountService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments = Payment::with('booking')
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->type, fn ($q, $type) => $type === Payment::TYPE_DP1
                ? $q->whereIn('type', [Payment::TYPE_DP1, 'dp10'])
                : $q->where('type', $type))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $totalOutstanding = Payment::where('status', Payment::STATUS_PENDING)->sum('amount');

        return view('admin.payments.index', compact('payments', 'totalOutstanding'));
    }

    public function verify(Payment $payment)
    {
        $payment->update([
            'status' => Payment::STATUS_VERIFIED,
            'paid_at' => now(),
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        $this->bookIfDp10($payment->booking);

        ActivityLogger::log(
            'payment_verified',
            Payment::typeLabel($payment->type).' dikonfirmasi',
            Payment::typeLabel($payment->type).' sebesar '.number_format($payment->amount).' dikonfirmasi oleh '.auth()->user()->name,
            $payment->booking_id,
        );

        return back()->with('success', 'Pembayaran diverifikasi.');
    }

    private function bookIfDp10(Booking $booking): void
    {
        if ($booking->status === Booking::STATUS_PENDING) {
            $hasVerifiedPayment = $booking->payments()
                ->where('status', Payment::STATUS_VERIFIED)
                ->exists();

            if ($hasVerifiedPayment) {
                $booking->update(['status' => Booking::STATUS_BOOKED]);

                $booking->ensureHariHSchedule();

                $account = app(ClientAccountService::class)->ensure($booking);

                ActivityLogger::log('dp_verified', 'Booking sah (BOOKED)', 'Pembayaran pertama terverifikasi, booking '.$booking->code.' sah.', $booking->id);

                if ($account) {
                    ActivityLogger::log('client_account_created', 'Akun portal dibuat otomatis', 'Akun portal client '.$account->email.' dibuat otomatis setelah DP terverifikasi.', $booking->id);
                }
            }
        }
    }
}
