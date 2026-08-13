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

        $hasVerifiedDp10 = $booking->payments
            ->contains(fn ($p) => $p->type === Payment::TYPE_DP10 && $p->status === Payment::STATUS_VERIFIED);

        if (! $hasVerifiedDp10) {
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

        $pelunasanVerified = $booking->payments
            ->contains(fn ($p) => $p->type === Payment::TYPE_PELUNASAN && $p->status === Payment::STATUS_VERIFIED);

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
                Booking::STATUS_PENDING => 'Menunggu DP 10%',
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
                ['key' => 'dp', 'label' => 'DP', 'state' => $statuses['dp']],
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
                ['Booking', 'done'], ['DP', 'done'],
                ['Survey', 'pending'], ['Fitting', 'pending'], ['Pelunasan', 'pending'],
                ['Hari H', 'pending'], ['Selesai', 'pending'],
            ])->map(fn ($s) => ['label' => $s[0], 'state' => $s[1]])->all(),
        ];
    }
}
