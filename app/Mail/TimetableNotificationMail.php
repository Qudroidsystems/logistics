<?php
// app/Mail/TimetableNotificationMail.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TimetableNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function envelope(): Envelope
    {
        $subject = match($this->data['type'] ?? 'daily_summary') {
            'daily_summary' => '📅 Your Timetable for Today',
            'weekly_preview' => '📆 Your Weekly Timetable Preview',
            'change_alert' => '🔄 Timetable Change Alert',
            default => 'Timetable Notification',
        };

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.timetable-notification',
        );
    }
}
