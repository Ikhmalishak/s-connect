<?php

namespace App\Mail;

use App\Models\ShipmentTransport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContainerReadyForDepartmentApproval extends Mailable
{
    use Queueable, SerializesModels;

    public $container;
    public $department;
    public $approvalType;

    /**
     * Create a new message instance.
     */
    public function __construct(ShipmentTransport $container, $department, $approvalType = 'loading')
    {
        $this->container = $container;
        $this->department = $department;
        $this->approvalType = $approvalType;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $typeText = $this->approvalType === 'inspection' ? 'Inspection' : 'Loading';
        return new Envelope(
            subject: "Container Ready for {$this->department} Department {$typeText} Approval",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.container-ready-for-department-approval',
            with: [
                'container' => $this->container,
                'department' => $this->department,
                'approvalType' => $this->approvalType,
                'url' => url('/container/approvals'),
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
