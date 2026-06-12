<?php

namespace App\Mail;

use App\Models\AuditSession;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotifyPicInspectionPass extends Mailable
{
    use Queueable, SerializesModels;

    public AuditSession $session;
    public User $pic;

    public function __construct(AuditSession $session, User $pic)
    {
        $this->session = $session;
        $this->pic = $pic;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Safety Inspection PASSED - {$this->session->auditType?->name} ({$this->session->site?->name})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pic-inspection-pass',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}