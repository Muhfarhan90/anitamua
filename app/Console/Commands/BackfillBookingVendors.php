<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillBookingVendors extends Command
{
    protected $signature = 'bookings:backfill-vendors {--force : Salin vendor paket ke booking yang belum memilikinya}';

    protected $description = 'Buat snapshot vendor untuk booking aktif yang belum memiliki vendor';

    public function handle(): int
    {
        $processed = 0;
        $preview = ! $this->option('force');

        Booking::query()
            ->where('status', '!=', Booking::STATUS_CANCELLED)
            ->whereDoesntHave('bookingVendors')
            ->whereHas('package.vendors')
            ->orderBy('id')
            ->chunkById(100, function ($bookings) use ($preview, &$processed) {
                foreach ($bookings as $booking) {
                    if ($preview) {
                        $processed++;

                        continue;
                    }

                    DB::transaction(function () use ($booking, &$processed) {
                        $booking = Booking::query()->lockForUpdate()->find($booking->id);

                        if (! $booking || $booking->bookingVendors()->exists()) {
                            return;
                        }

                        $booking->syncVendorsFromPackage();
                        $processed++;
                    });
                }
            });

        if ($preview) {
            $this->warn("Preview: {$processed} booking akan diberi snapshot vendor. Jalankan dengan --force untuk menyimpan.");
        } else {
            $this->info("Snapshot vendor dibuat: {$processed}");
        }

        return self::SUCCESS;
    }
}
