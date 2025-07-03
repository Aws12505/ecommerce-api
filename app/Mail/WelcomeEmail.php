<?php
// FILE: app/Mail/WelcomeEmail.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to ' . config('app.name') . '!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.welcome',
            with: [
                'user' => $this->user,
                'appName' => config('app.name'),
                'appUrl' => config('app.url'),
                'supportEmail' => config('mail.support_email', 'support@' . parse_url(config('app.url'), PHP_URL_HOST)),
                'dashboardUrl' => config('app.frontend_url') . '/dashboard',
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
