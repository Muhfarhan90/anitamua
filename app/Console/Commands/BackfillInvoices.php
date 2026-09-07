<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Payment;
use App\Services\InvoiceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillInvoices extends Command
{
    protected $signature = 'invoices:backfill {--force : Buat invoice yang belum ada}';

    protected $description = 'Buat invoice untuk booking Booked/Completed yang belum memiliki invoice';

    public function handle(InvoiceService $invoiceService): int
    {
        $created = 0;
        $preview = ! $this->option('force');

        Booking::query()
            ->whereIn('status', [Booking::STATUS_BOOKED, Booking::STATUS_COMPLETED])
            ->whereDoesntHave('invoice')
            ->orderBy('id')
            ->chunkById(100, function ($bookings) use ($invoiceService, $preview, &$created) {
                foreach ($bookings as $booking) {
                    if ($preview) {
                        $created++;

                        continue;
                    }

                    DB::transaction(function () use ($booking, $invoiceService, &$created) {
                        $booking = Booking::query()->lockForUpdate()->find($booking->id);

                        if (! $booking || $booking->invoice()->exists()) {
                            return;
                        }

                        $firstPayment = $booking->payments()
                            ->where('status', Payment::STATUS_VERIFIED)
                            ->orderByRaw('COALESCE(paid_at, verified_at, created_at)')
                            ->first();
                        $issueDate = $firstPayment?->paid_at
                            ?? $firstPayment?->verified_at
                            ?? $firstPayment?->created_at
                            ?? $booking->created_at
                            ?? today();

                        $invoice = $invoiceService->sync($booking);
                        $invoice?->update(['issue_date' => $issueDate->toDateString()]);
                        $created++;
                    });
                }
            });

        if ($preview) {
            $this->warn("Preview: {$created} invoice akan dibuat. Jalankan dengan --force untuk menyimpan.");
        } else {
            $this->info("Invoice dibuat: {$created}");
        }

        return self::SUCCESS;
    }
}
