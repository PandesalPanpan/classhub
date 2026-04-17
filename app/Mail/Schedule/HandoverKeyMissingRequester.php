<?php

namespace App\Mail\Schedule;

use App\Models\Schedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HandoverKeyMissingRequester extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Schedule $schedule
    ) {}

    public function envelope(): Envelope
    {
        $roomInfo = $this->schedule->room?->room_number ?? 'Assigned Room';

        return new Envelope(
            subject: "URGENT: Return the Room Key Immediately — {$roomInfo}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.schedule.handover-key-missing-requester',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
