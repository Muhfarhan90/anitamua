<?php

namespace App\Services;

use App\Mail\ClientAccountCredentials;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Membuat akun client otomatis sesuai PRD:
 * akun dashboard dibuat saat admin memverifikasi DP1 (status BOOKED).
 * Email berisi kredensial login dikirim ke client saat akun baru dibuat.
 */
class ClientAccountService
{
    public function ensure(Booking $booking, bool $sendCredentials = true): ?User
    {
        if ($booking->client_id) {
            $user = User::find($booking->client_id);

            if (! $user?->isClient()) {
                throw ValidationException::withMessages([
                    'client_id' => 'Booking terhubung ke akun non-client. Pilih akun client yang benar terlebih dahulu.',
                ]);
            }

            $this->fillMissingClientProfile($user, $booking);
            $this->syncBookingIdentity($booking, $user);

            return $user;
        }

        $email = strtolower(trim((string) $booking->email));
        if (! $email) {
            throw ValidationException::withMessages([
                'email' => 'Email client wajib diisi sebelum booking dapat disahkan.',
            ]);
        }

        $user = User::where('email', $email)->first();
        $isNew = false;

        if ($user && ! $user->isClient()) {
            throw ValidationException::withMessages([
                'email' => 'Email booking sudah digunakan oleh akun '.strtoupper($user->role).'. Gunakan email client yang benar.',
            ]);
        }

        if ($user && filled($user->phone) && $this->normalizePhone($booking->phone) !== $this->normalizePhone($user->phone)) {
            throw ValidationException::withMessages([
                'phone' => 'Nomor telepon tidak cocok dengan akun client yang menggunakan email tersebut.',
            ]);
        }

        if (! $user) {
            $isNew = true;

            $user = User::create([
                'name' => $booking->name,
                'email' => $email,
                'phone' => $booking->phone,
                'instagram' => $booking->instagram,
                'password' => User::generateDefaultPassword($booking->name),
                'role' => User::ROLE_CLIENT,
                'position' => 'Bride',
            ]);
        }

        $this->fillMissingClientProfile($user, $booking);
        $booking->client_id = $user->id;
        $this->syncBookingIdentity($booking, $user);

        if ($isNew && $sendCredentials) {
            $password = User::generateDefaultPassword($booking->name);
            DB::afterCommit(fn () => $this->sendCredentials($user, $password, $booking));
        }

        return $user;
    }

    private function fillMissingClientProfile(User $user, Booking $booking): void
    {
        if (blank($user->phone)) {
            $user->update(['phone' => $booking->phone]);
        }
    }

    private function syncBookingIdentity(Booking $booking, User $user): void
    {
        $booking->update([
            'client_id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'instagram' => $user->instagram,
        ]);
    }

    private function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (str_starts_with($digits, '62')) {
            return '0'.substr($digits, 2);
        }

        return str_starts_with($digits, '8') ? '0'.$digits : $digits;
    }

    private function sendCredentials(User $user, string $password, Booking $booking): void
    {
        try {
            Mail::to($user->email)->send(
                new ClientAccountCredentials($user, $password, $booking->code)
            );
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim kredensial akun client: '.$e->getMessage());
        }
    }
}
