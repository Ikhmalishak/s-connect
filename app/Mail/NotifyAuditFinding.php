<?php

namespace App\Mail;

use App\Models\AuditSession;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotifyAuditFinding extends Mailable
{
    use Queueable, SerializesModels;

    public $session;
    public $failedItems;
    public $pic;

    /**
     * Create a new message instance.
     */
    public function __construct(AuditSession $session, array $failedItems, User $pic)
    {
        $this->session = $session;
        $this->failedItems = $failedItems;
        $this->pic = $pic;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $siteName = $this->session->site?->name ?? 'Unknown Site';

        return new Envelope(
            subject: "EHS Audit Finding - {$siteName}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.audit-finding',
            with: [
                'session' => $this->session,
                'failedItems' => $this->failedItems,
                'pic' => $this->pic,
                'url' => url('/safety/dashboard'),
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