<?php

namespace App\Mail;

use App\Models\ShipmentTransport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContainerApprovedForLoading extends Mailable
{
    use Queueable, SerializesModels;

    public $container;

    /**
     * Create a new message instance.
     */
    public function __construct(ShipmentTransport $container)
    {
        $this->container = $container;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Container Approved - Please Upload Loading Photos',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.container-approved-for-loading',
            with: [
                'container' => $this->container,
                'url' => url('/container/dashboard'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
