<?php

namespace App\Helpers;

use App\Models\Booking;
use App\Models\Fitting;
use App\Models\Payment;
use App\Models\Schedule;

class BookingProgress
{
    public static function calculate(Booking $booking): array
    {
        $statuses = [
            'booking' => 'done',
            'dp' => 'done',
            'survey' => 'pending',
            'fitting' => 'pending',
            'pelunasan' => 'pending',
            'hari_h' => 'pending',
            'selesai' => 'pending',
        ];

        if ($booking->status === Booking::STATUS_CANCELLED) {
            return self::cancelled($booking);
        }

        $initialPayment = $booking->payments->sortBy('id')->first();
        $hasVerifiedDp1 = $initialPayment?->status === Payment::STATUS_VERIFIED;

        if (! $hasVerifiedDp1) {
            $statuses['dp'] = 'current';
        }

        $surveyDone = $booking->survey !== null
            || $booking->schedules->contains(fn ($s) => $s->type === Schedule::TYPE_SURVEY && $s->status === Schedule::STATUS_FINISHED);

        if ($surveyDone) {
            $statuses['survey'] = 'done';
        }

        $fittingDone = $booking->fittings->contains(fn ($f) => $f->status === Fitting::STATUS_FINISHED);

        if ($fittingDone) {
            $statuses['fitting'] = 'done';
        }

        $verifiedTotal = (float) $booking->payments->where('status', Payment::STATUS_VERIFIED)->sum('amount');
        $pelunasanVerified = ($booking->total_price <= 0 && $hasVerifiedDp1)
            || ($booking->total_price > 0 && $verifiedTotal >= $booking->total_price);

        if ($pelunasanVerified) {
            $statuses['pelunasan'] = 'done';
        }

        if ($booking->status === Booking::STATUS_COMPLETED || $booking->event_date < now()->startOfDay()) {
            $statuses['hari_h'] = 'done';
            $statuses['selesai'] = 'done';
        }

        return [
            'status' => $booking->status,
            'status_label' => match ($booking->status) {
                Booking::STATUS_PENDING => 'Menunggu DP1',
                Booking::STATUS_BOOKED => 'BOOKED',
                Booking::STATUS_COMPLETED => 'Selesai',
                Booking::STATUS_CANCELLED => 'Cancelled',
                default => ucfirst($booking->status),
            },
            'status_badge' => match ($booking->status) {
                Booking::STATUS_PENDING => 'text-bg-warning',
                Booking::STATUS_BOOKED => 'text-bg-success',
                Booking::STATUS_COMPLETED => 'text-bg-primary',
                Booking::STATUS_CANCELLED => 'text-bg-danger',
                default => 'text-bg-secondary',
            },
            'steps' => [
                ['key' => 'booking', 'label' => 'Booking', 'state' => $statuses['booking']],
                ['key' => 'dp', 'label' => 'DP1', 'state' => $statuses['dp']],
                ['key' => 'survey', 'label' => 'Survey', 'state' => $statuses['survey']],
                ['key' => 'fitting', 'label' => 'Fitting', 'state' => $statuses['fitting']],
                ['key' => 'pelunasan', 'label' => 'Pelunasan', 'state' => $statuses['pelunasan']],
                ['key' => 'hari_h', 'label' => 'Hari H', 'state' => $statuses['hari_h']],
                ['key' => 'selesai', 'label' => 'Selesai', 'state' => $statuses['selesai']],
            ],
        ];
    }

    private static function cancelled(Booking $booking): array
    {
        return [
            'status' => $booking->status,
            'status_label' => 'Cancelled',
            'status_badge' => 'text-bg-danger',
            'steps' => collect([
                ['Booking', 'done'], ['DP1', 'done'],
                ['Survey', 'pending'], ['Fitting', 'pending'], ['Pelunasan', 'pending'],
                ['Hari H', 'pending'], ['Selesai', 'pending'],
            ])->map(fn ($s) => ['label' => $s[0], 'state' => $s[1]])->all(),
        ];
    }
}
