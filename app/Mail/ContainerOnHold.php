<?php

namespace App\Mail;

use App\Models\ShipmentTransport;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContainerOnHold extends Mailable
{
    use Queueable, SerializesModels;

    public $container;
    public $user;

    /**
     * Create a new message instance.
     */
    public function __construct(ShipmentTransport $container, User $user)
    {
        $this->container = $container;
        $this->user = $user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Container Put On Hold - Quality Department',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.container-on-hold',
            with: [
                'container' => $this->container,
                'user' => $this->user,
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
