<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientAccountCredentials extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $password,
        public ?string $bookingCode = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Akun Dashboard Anda — Anita MUA',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.client-account',
        );
    }
}
