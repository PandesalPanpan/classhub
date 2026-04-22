<?php

namespace App\Mail\Key;

use App\Models\Schedule;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class KeyMissing extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Schedule $lastSchedule,
        public User $recipient
    ) {}

    public function envelope(): Envelope
    {
        $roomInfo = $this->lastSchedule->room?->room_number ?? 'Unknown Room';

        return new Envelope(
            subject: "URGENT: Key Missing - Room {$roomInfo}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.key.missing',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
