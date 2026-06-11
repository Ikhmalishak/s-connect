<?php

namespace App\Mail;

use App\Models\AuditSession;
use App\Models\AuditFindingAction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotifySafetyTeamApproval extends Mailable
{
    use Queueable, SerializesModels;

    public $session;
    public $action;
    public $approver;

    /**
     * Create a new message instance.
     */
    public function __construct(AuditSession $session, AuditFindingAction $action, User $approver)
    {
        $this->session = $session;
        $this->action = $action;
        $this->approver = $approver;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $siteName = $this->session->site?->name ?? 'Unknown Site';

        return new Envelope(
            subject: "EHS Audit - Corrective Action Submitted – {$siteName}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.safety-approval',
            with: [
                'session' => $this->session,
                'action' => $this->action,
                'approver' => $this->approver,
                'url' => url('/safety/approvals'),
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