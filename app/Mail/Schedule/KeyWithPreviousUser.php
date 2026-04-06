<?php

namespace App\Mail\Schedule;

use App\Models\Schedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class KeyWithPreviousUser extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Schedule $schedule,
        public Schedule $previousSchedule
    ) {}

    public function envelope(): Envelope
    {
        $roomInfo = $this->schedule->room?->room_number ?? 'Assigned Room';
        $dateInfo = $this->schedule->start_time->format('M j, Y');

        return new Envelope(
            subject: "Key Currently in Use - {$roomInfo} - {$dateInfo}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.schedule.key-with-previous-user',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
