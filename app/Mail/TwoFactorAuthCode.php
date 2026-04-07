<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TwoFactorAuthCode extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'ログイン認証コードのご案内【' . config('app.name') . '】',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.two-factor-auth-code',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
