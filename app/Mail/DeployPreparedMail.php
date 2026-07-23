<?php

namespace App\Mail;

use App\Models\RdmInstance;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeployPreparedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RdmInstance $instance,
        public User $user,
        public bool $accountCreated,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Akun & RDM '.$this->instance->fqdn.' — lanjutkan pembayaran',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.deploy-prepared',
        );
    }
}
