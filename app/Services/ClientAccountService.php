<?php

namespace App\Services;

use App\Mail\ClientAccountCredentials;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Membuat akun client otomatis sesuai PRD:
 * akun dashboard dibuat saat admin memverifikasi DP1 (status BOOKED).
 * Email berisi kredensial login dikirim ke client saat akun baru dibuat.
 */
class ClientAccountService
{
    public function ensure(Booking $booking): ?User
    {
        if ($booking->client_id) {
            return null;
        }

        $email = $booking->email;
        if (! $email) {
            return null;
        }

        $user = User::where('email', $email)->where('role', User::ROLE_CLIENT)->first();
        $isNew = false;

        if (! $user) {
            $isNew = true;

            $user = User::create([
                'name' => $booking->name,
                'email' => $email,
                'phone' => $booking->phone,
                'instagram' => $booking->instagram,
                'password' => Hash::make(User::generateDefaultPassword($booking->name)),
                'role' => User::ROLE_CLIENT,
                'position' => 'Bride',
            ]);
        }

        $booking->update(['client_id' => $user->id]);

        if ($isNew) {
            $this->sendCredentials($user, User::generateDefaultPassword($booking->name), $booking);
        }

        return $user;
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
