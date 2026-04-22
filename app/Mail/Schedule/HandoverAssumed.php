<?php

namespace App\Mail\Schedule;

use App\Models\Schedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HandoverAssumed extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Schedule $previousSchedule,
        public Schedule $nextSchedule,
        public string $recipientRole // 'previous' or 'next'
    ) {}

    public function envelope(): Envelope
    {
        $roomInfo = $this->nextSchedule->room?->room_number ?? 'Assigned Room';
        $dateInfo = $this->nextSchedule->start_time->format('M j, Y');

        if ($this->recipientRole === 'previous') {
            $subject = "Handover Assumed - {$roomInfo} - {$dateInfo}";
        } else {
            $subject = "Key Handover Assumed - {$roomInfo} - {$dateInfo}";
        }

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.schedule.handover-assumed',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
