<?php

namespace App\Mail;

use App\Models\RdmInstance;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeployPaymentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RdmInstance $instance,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pengingat: selesaikan pembayaran RDM '.$this->instance->fqdn,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.deploy-payment-reminder',
        );
    }
}
