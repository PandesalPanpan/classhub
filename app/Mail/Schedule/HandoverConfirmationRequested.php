<?php

namespace App\Mail\Schedule;

use App\Models\ScheduleHandover;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class HandoverConfirmationRequested extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $confirmUrl;

    public string $disputeUrl;

    public function __construct(
        public ScheduleHandover $handover,
        public string $recipientRole
    ) {
        $expiry = $this->handover->resolution_deadline_at;

        $this->confirmUrl = URL::temporarySignedRoute(
            'handover.confirm',
            $expiry,
            ['handover' => $this->handover->id, 'role' => $this->recipientRole]
        );

        $this->disputeUrl = URL::temporarySignedRoute(
            'handover.dispute',
            $expiry,
            ['handover' => $this->handover->id, 'role' => $this->recipientRole]
        );
    }

    public function envelope(): Envelope
    {
        $handover = $this->handover;
        $handover->loadMissing(['previousSchedule.room', 'nextSchedule']);

        $roomInfo = $handover->previousSchedule->room?->room_number ?? 'Assigned Room';
        $dateInfo = $handover->previousSchedule->end_time->format('M j, Y');

        $subject = $this->recipientRole === 'previous'
            ? "Action Required: Key Handover Confirmation — {$roomInfo} {$dateInfo}"
            : "Action Required: Key Handover Confirmation — {$roomInfo} {$dateInfo}";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.schedule.handover-confirmation-requested',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
