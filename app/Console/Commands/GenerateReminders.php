<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateReminders extends Command
{
    protected $signature = 'reminders:generate';

    protected $description = 'Generate reminder otomatis (H-30 fitting, H-7 DP 75%, H-2 pelunasan, H-1 hari H) untuk semua booking aktif';

    public function handle(): int
    {
        $bookings = Booking::with('payments')
            ->where('status', Booking::STATUS_BOOKED)
            ->where('event_date', '>=', Carbon::today())
            ->get();

        $generated = 0;

        foreach ($bookings as $booking) {
            $eventDate = Carbon::parse($booking->event_date);
            $fittingDate = $booking->fitting_date
                ? Carbon::parse($booking->fitting_date)
                : $eventDate->copy()->subDays(14);

            $rules = [
                ['h30', 'Reminder Fitting', $fittingDate->copy()->subDays(30), 'client'],
                ['h7', 'Reminder DP 75%', $eventDate->copy()->subDays(7), 'client'],
                ['h2', 'Reminder Pelunasan', $eventDate->copy()->subDays(2), 'client'],
                ['h1', 'Reminder Hari H', $eventDate->copy()->subDays(1), 'client'],
            ];

            foreach ($rules as [$type, $title, $date, $audience]) {
                $exists = $booking->reminders()
                    ->where('type', $type)
                    ->where('scheduled_at', $date->toDateString())
                    ->exists();

                if (! $exists) {
                    $booking->reminders()->create([
                        'type' => $type,
                        'title' => $title,
                        'message' => "Jangan lupa: {$title} untuk acara {$booking->name} ({$booking->code}). Terima kasih!",
                        'scheduled_at' => $date->toDateString(),
                        'audience' => $audience,
                        'channel' => 'whatsapp',
                        'status' => 'pending',
                    ]);
                    $generated++;
                }
            }
        }

        $this->info("Reminder dibuat: {$generated}");

        return self::SUCCESS;
    }
}
