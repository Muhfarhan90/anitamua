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
        $query = Payment::with('booking')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->orderByDesc('created_at');

        $payments = $query->paginate(20)->withQueryString();

        $totalOutstanding = Payment::where('status', Payment::STATUS_PENDING)->sum('amount');

        return view('admin.payments.index', compact('payments', 'totalOutstanding'));
    }

    public function update(Booking $booking, Payment $payment, Request $request)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'method' => ['required', 'string'],
            'status' => ['required', 'in:pending,verified,cancelled'],
            'notes' => ['nullable', 'string'],
        ]);

        $wasVerified = $payment->status === Payment::STATUS_VERIFIED;

        $payment->update([
            'amount' => $data['amount'],
            'method' => $data['method'],
            'status' => $data['status'],
            'notes' => $data['notes'],
            'verified_by' => $data['status'] === Payment::STATUS_VERIFIED ? auth()->id() : $payment->verified_by,
            'verified_at' => $data['status'] === Payment::STATUS_VERIFIED ? now() : $payment->verified_at,
        ]);

        if ($data['status'] === Payment::STATUS_VERIFIED && ! $wasVerified) {
            $this->bookIfDp10($booking);

            ActivityLogger::log(
                'payment_verified',
                Payment::typeLabel($payment->type).' dikonfirmasi',
                Payment::typeLabel($payment->type).' sebesar '.number_format($payment->amount).' dikonfirmasi oleh '.auth()->user()->name,
                $booking->id,
            );
        }

        return back()->with('success', 'Pembayaran diperbarui.');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'booking_id' => ['required', 'exists:bookings,id'],
            'type' => ['required', 'string', 'max:100'], // label tahap bebas
            'amount' => ['required', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'method' => ['nullable', 'string', 'max:50'],
        ]);

        $payment = Payment::create([
            'booking_id' => $data['booking_id'],
            'type' => $data['type'],
            'amount' => $data['amount'],
            'due_date' => $data['due_date'] ?? null,
            'method' => $data['method'] ?? 'transfer',
            'status' => Payment::STATUS_PENDING,
        ]);

        ActivityLogger::log(
            'payment_stage_created',
            'Tahap pembayaran ditambahkan',
            'Tahap "'.$payment->type.'" (Rp '.number_format($payment->amount).') ditambahkan oleh '.auth()->user()->name,
            $data['booking_id'],
        );

        return back()->with('success', 'Tahap pembayaran berhasil ditambahkan.');
    }

    public function destroy(Payment $payment)
    {
        $bookingId = $payment->booking_id;

        if ($payment->status === Payment::STATUS_VERIFIED) {
            return back()->with('warning', 'Tahap yang sudah terverifikasi tidak bisa dihapus.');
        }

        $payment->delete();

        ActivityLogger::log('payment_stage_deleted', 'Tahap pembayaran dihapus', 'Tahap pembayaran dihapus oleh '.auth()->user()->name, $bookingId);

        return back()->with('success', 'Tahap pembayaran dihapus.');
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
