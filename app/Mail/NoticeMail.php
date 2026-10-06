<?php

namespace App\Mail;

use App\Models\SchoolInformation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NoticeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $text,
        public ?string $recipientName = null,
        public ?string $fromName = null,
        public ?string $replyToAddress = null,
        public ?array $attachment = null, // ['path' => absolute path, 'filename' => 'Report.pdf']
    ) {}

    public function attachments(): array
    {
        if (!$this->attachment || empty($this->attachment['path']) || !is_file($this->attachment['path'])) {
            return [];
        }
        return [
            Attachment::fromPath($this->attachment['path'])
                ->as($this->attachment['filename'] ?? basename($this->attachment['path']))
                ->withMime($this->attachment['mime'] ?? 'application/pdf'),
        ];
    }

    public function envelope(): Envelope
    {
        $from = config('mail.from.address');
        return new Envelope(
            from: $from ? new Address($from, $this->fromName ?: config('mail.from.name')) : null,
            replyTo: $this->replyToAddress && filter_var($this->replyToAddress, FILTER_VALIDATE_EMAIL) ? [new Address($this->replyToAddress)] : [],
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        $school = SchoolInformation::getActiveSchool() ?? SchoolInformation::first();
        return new Content(
            view: 'emails.notice',
            text: 'emails.notice-text',
            with: ['school' => $school, 'text' => $this->text, 'subjectLine' => $this->subjectLine],
        );
    }
}
