<?php

namespace App\Models;

use App\ScheduleStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Schedule extends Model
{
    public function approve(): void
    {
        $this->update([
            'status' => ScheduleStatus::Approved,
            'approver_id' => Auth::id(),
        ]);
    }

    public function reject(): void
    {
        $this->update([
            'status' => ScheduleStatus::Rejected,
            'approver_id' => Auth::id(),
        ]);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => ScheduleStatus::Cancelled,
        ]);
    }

    public function expire(): void
    {
        $this->update([
            'status' => ScheduleStatus::Expired,
        ]);
    }

    /**
     * Calculate the datetime when 40% of the schedule duration has elapsed from the start time.
     * This is used to determine when to verify key usage.
     */
    public function getFortyPercentDurationPoint(): \Illuminate\Support\Carbon
    {
        $durationInSeconds = $this->start_time->diffInSeconds($this->end_time);
        $delayInSeconds = (int) ($durationInSeconds * 0.4);

        return $this->start_time->copy()->addSeconds($delayInSeconds);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id', 'id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id', 'id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id', 'id');
    }

    protected function casts(): array
    {
        return [
            'status' => ScheduleStatus::class,
            'start_time' => 'datetime',
            'end_time' => 'datetime',
        ];
    }

    protected function eventTitle(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                return $this->subject.' ('.$this->program_year_section.')'.' - '.$this->instructorInitials;
            },
        );
    }

    protected function instructorInitials(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if (empty($this->instructor)) {
                    return '';
                }

                $nameParts = explode(' ', trim($this->instructor));

                if (count($nameParts) === 1) {
                    return $nameParts[0];
                }

                $lastName = array_pop($nameParts);
                $initials = array_map(fn ($part) => strtoupper(substr($part, 0, 1)).'.', $nameParts);

                return implode('', $initials).' '.$lastName;
            },
        );
    }
}
