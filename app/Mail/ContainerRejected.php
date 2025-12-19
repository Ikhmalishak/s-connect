<?php

namespace App\Mail;

use App\Models\ShipmentTransport;
use App\Models\ShipmentTransportApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContainerRejected extends Mailable
{
    use Queueable, SerializesModels;

    public $container;
    public $approval;

    /**
     * Create a new message instance.
     */
    public function __construct(ShipmentTransport $container, ShipmentTransportApproval $approval)
    {
        $this->container = $container;
        $this->approval = $approval;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Container Rejected - Action Required',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.container-rejected',
            with: [
                'container' => $this->container,
                'approval' => $this->approval,
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
