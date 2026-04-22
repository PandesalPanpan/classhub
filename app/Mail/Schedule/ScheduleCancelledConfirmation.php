<?php

namespace App\Mail\Schedule;

use App\Models\Schedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ScheduleCancelledConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Schedule $schedule
    ) {}

    public function envelope(): Envelope
    {
        $roomInfo = $this->schedule->room?->room_number ?? 'Room';
        $dateInfo = $this->schedule->start_time->format('M j, Y');

        return new Envelope(
            subject: "Schedule Request Cancelled - {$roomInfo} - {$dateInfo}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.schedule.cancelled-confirmation',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
