<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ClientAccountService;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments = Payment::with('booking')
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $totalOutstanding = Payment::where('status', Payment::STATUS_PENDING)->sum('amount');

        return view('admin.payments.index', compact('payments', 'totalOutstanding'));
    }

    public function verify(Request $request, Payment $payment, InvoiceService $invoiceService)
    {
        if ($payment->status !== Payment::STATUS_PENDING) {
            return back()->with('warning', 'Hanya pembayaran berstatus Pending yang dapat diverifikasi.');
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        $account = DB::transaction(function () use ($payment, $data, $invoiceService) {
            $payment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            if ($payment->status !== Payment::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'amount' => 'Hanya pembayaran berstatus Pending yang dapat diverifikasi.',
                ]);
            }

            $payment->update([
                'amount' => $data['amount'],
                'status' => Payment::STATUS_VERIFIED,
                'paid_at' => now(),
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);

            $account = $this->bookIfFirstPayment($payment->booking);
            $invoiceService->sync($payment->booking->fresh());

            ActivityLogger::log(
                'payment_verified',
                Payment::typeLabel($payment->type).' dikonfirmasi',
                Payment::typeLabel($payment->type).' sebesar '.number_format($payment->amount).' dikonfirmasi oleh '.auth()->user()->name,
                $payment->booking_id,
            );

            return $account;
        });

        $message = 'Pembayaran diverifikasi.';
        if ($account?->wasRecentlyCreated) {
            $message .= ' Akun client '.$account->email.' dibuat dengan password default '.User::generateDefaultPassword($account->name).'.';
        }

        return back()->with('success', $message);
    }

    public function correctAmount(Request $request, Payment $payment, InvoiceService $invoiceService)
    {
        if ($payment->status !== Payment::STATUS_VERIFIED) {
            return back()->with('warning', 'Nominal hanya dapat diedit pada pembayaran berstatus Verified.');
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($payment, $data, $invoiceService) {
            $oldAmount = $payment->amount;
            $payment->update(['amount' => $data['amount']]);
            $invoiceService->sync($payment->booking->fresh());

            ActivityLogger::log(
                'payment_amount_corrected',
                'Nominal pembayaran diedit',
                Payment::typeLabel($payment->type).' diedit dari '.number_format($oldAmount).' menjadi '.number_format($payment->amount).' oleh '.auth()->user()->name,
                $payment->booking_id,
                ['amount' => $oldAmount],
                ['amount' => $payment->amount],
            );
        });

        return back()->with('success', 'Nominal pembayaran berhasil diedit. Status tetap Verified.');
    }

    private function bookIfFirstPayment(Booking $booking): ?User
    {
        if ($booking->status === Booking::STATUS_PENDING) {
            $hasVerifiedPayment = $booking->payments()
                ->where('type', 'DP1')
                ->where('status', Payment::STATUS_VERIFIED)
                ->exists();

            if ($hasVerifiedPayment) {
                $booking->update(['status' => Booking::STATUS_BOOKED]);

                $booking->ensureHariHSchedule();

                $account = app(ClientAccountService::class)->ensure($booking);

                ActivityLogger::log('dp_verified', 'Booking sah (BOOKED)', 'Pembayaran pertama terverifikasi, booking '.$booking->code.' sah.', $booking->id);

                if ($account) {
                    ActivityLogger::log(
                        $account->wasRecentlyCreated ? 'client_account_created' : 'client_account_linked',
                        $account->wasRecentlyCreated ? 'Akun portal dibuat otomatis' : 'Booking ditautkan ke akun client',
                        'Booking '.$booking->code.' ditautkan ke akun client '.$account->email.'.',
                        $booking->id,
                    );
                }

                return $account;
            }
        }

        return null;
    }
}
