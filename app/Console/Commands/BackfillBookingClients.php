<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\User;
use App\Services\ClientAccountService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BackfillBookingClients extends Command
{
    protected $signature = 'bookings:backfill-clients
        {--force : Simpan hasil backfill}
        {--send-credentials : Kirim email kredensial untuk akun yang baru dibuat}';

    protected $description = 'Tautkan dan sinkronkan booking Booked/Completed dengan akun client';

    public function handle(ClientAccountService $clientAccounts): int
    {
        $preview = ! $this->option('force');
        $clients = User::where('role', User::ROLE_CLIENT)->get();
        $clientsByPhone = $clients
            ->filter(fn (User $user) => filled($user->phone))
            ->groupBy(fn (User $user) => $this->normalizePhone($user->phone));
        $rows = [];
        $linked = 0;
        $created = 0;
        $unresolved = 0;

        $bookings = Booking::query()
            ->with('client')
            ->whereIn('status', [Booking::STATUS_BOOKED, Booking::STATUS_COMPLETED])
            ->orderBy('id')
            ->get()
            ->filter(fn (Booking $booking) => $this->needsSync($booking));

        foreach ($bookings as $booking) {
            $match = $this->findClient($booking, $clientsByPhone);
            $emailOwner = filled($booking->email)
                ? User::where('email', strtolower(trim($booking->email)))->first()
                : null;

            if ($match) {
                $action = $booking->client?->isClient() ? 'sinkronkan' : 'tautkan';
                $target = $match->email;
            } elseif ($this->canCreateAccount($booking, $emailOwner)) {
                $action = 'buat akun';
                $target = strtolower(trim($booking->email));
            } else {
                $action = 'perlu diperiksa';
                $target = $emailOwner
                    ? 'email milik '.$emailOwner->role
                    : 'email/telepon tidak dapat dicocokkan';
            }

            $rows[] = [$booking->code, $booking->client?->email ?? '-', $booking->email ?? '-', $booking->phone, $action, $target];

            if ($preview) {
                match ($action) {
                    'tautkan', 'sinkronkan' => $linked++,
                    'buat akun' => $created++,
                    default => $unresolved++,
                };

                continue;
            }

            if ($action === 'perlu diperiksa') {
                $unresolved++;

                continue;
            }

            DB::transaction(function () use ($booking, $match, $clientAccounts, &$linked, &$created) {
                $booking = Booking::query()->lockForUpdate()->findOrFail($booking->id);

                if ($match) {
                    $booking->client_id = $match->id;
                    $clientAccounts->ensure($booking, false);
                    $linked++;

                    return;
                }

                $booking->update(['client_id' => null]);
                $clientAccounts->ensure($booking, (bool) $this->option('send-credentials'));
                $created++;
            });
        }

        $this->table(
            ['Booking', 'Client lama', 'Email booking', 'Telepon', 'Aksi', 'Target/catatan'],
            $rows,
        );

        $label = $preview ? 'Preview' : 'Selesai';
        $this->info("{$label}: {$linked} ditautkan, {$created} akun dibuat, {$unresolved} perlu diperiksa manual.");
        if ($preview && $bookings->isNotEmpty()) {
            $this->warn('Belum ada data yang diubah. Jalankan ulang dengan --force setelah memeriksa tabel di atas.');
        }

        return $unresolved > 0 ? self::INVALID : self::SUCCESS;
    }

    private function findClient(Booking $booking, Collection $clientsByPhone): ?User
    {
        if ($booking->client?->isClient()) {
            return $booking->client;
        }

        if (filled($booking->email)) {
            $client = User::where('email', strtolower(trim($booking->email)))
                ->where('role', User::ROLE_CLIENT)
                ->first();

            if ($client) {
                return $client;
            }
        }

        $phone = $this->normalizePhone($booking->phone);
        $matches = $phone !== '' ? $clientsByPhone->get($phone, collect()) : collect();

        return $matches->count() === 1 ? $matches->first() : null;
    }

    private function needsSync(Booking $booking): bool
    {
        if (! $booking->client?->isClient()) {
            return true;
        }

        return $booking->name !== $booking->client->name
            || $this->normalizePhone($booking->phone) !== $this->normalizePhone($booking->client->phone)
            || strtolower(trim((string) $booking->email)) !== strtolower(trim((string) $booking->client->email))
            || ($booking->instagram ?? '') !== ($booking->client->instagram ?? '');
    }

    private function canCreateAccount(Booking $booking, ?User $emailOwner): bool
    {
        return ! $emailOwner
            && filter_var($booking->email, FILTER_VALIDATE_EMAIL)
            && filled($booking->name)
            && filled($booking->phone);
    }

    private function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (str_starts_with($digits, '62')) {
            return '0'.substr($digits, 2);
        }

        return str_starts_with($digits, '8') ? '0'.$digits : $digits;
    }
}
