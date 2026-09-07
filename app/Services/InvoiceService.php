<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Payment;

class InvoiceService
{
    public function sync(Booking $booking): ?Invoice
    {
        if (! in_array($booking->status, [Booking::STATUS_BOOKED, Booking::STATUS_COMPLETED], true)) {
            return null;
        }

        $booking->loadMissing(['client', 'package', 'addons', 'payments']);
        $items = [];

        if ($booking->package) {
            $price = (float) ($booking->package_price ?? $booking->package->price);
            $items[] = [
                'name' => $booking->package->name,
                'quantity' => 1,
                'price' => $price,
                'total' => $price,
            ];
        }

        foreach ($booking->addons as $addon) {
            $price = (float) $addon->price;
            $items[] = [
                'name' => $addon->name,
                'quantity' => 1,
                'price' => $price,
                'total' => $price,
            ];
        }

        $totalAmount = collect($items)->sum('total');
        $paidAmount = $booking->payments
            ->where('status', Payment::STATUS_VERIFIED)
            ->sum(fn (Payment $payment) => (float) $payment->amount);

        $invoice = Invoice::firstOrNew(['booking_id' => $booking->id]);
        $invoice->fill([
            'invoice_number' => $invoice->invoice_number ?: Invoice::generateInvoiceNumber($booking),
            'issue_date' => $invoice->issue_date ?: today(),
            'due_date' => $invoice->due_date ?: $booking->event_date?->copy()->subDay(),
            'items' => $items,
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'remaining_amount' => max(0, $totalAmount - $paidAmount),
            'status' => $paidAmount >= $totalAmount && $totalAmount > 0
                ? Invoice::STATUS_PAID
                : ($paidAmount > 0 ? Invoice::STATUS_PARTIAL : Invoice::STATUS_UNPAID),
        ])->save();

        return $invoice->fresh('booking');
    }
}
