<?php

namespace App\Models;

use App\ScheduleStatus;
use App\ScheduleType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Schedule extends Model
{
    /**
     * Scope: pending schedules that match the exact room and time slot.
     */
    public function scopePendingForSlot(Builder $query, int $roomId, string|\DateTimeInterface $start, string|\DateTimeInterface $end): Builder
    {
        $startStr = $start instanceof \DateTimeInterface
            ? Carbon::instance($start)->format('Y-m-d H:i:s')
            : Carbon::parse($start)->format('Y-m-d H:i:s');
        $endStr = $end instanceof \DateTimeInterface
            ? Carbon::instance($end)->format('Y-m-d H:i:s')
            : Carbon::parse($end)->format('Y-m-d H:i:s');

        return $query
            ->where('status', ScheduleStatus::Pending)
            ->where('room_id', $roomId)
            ->where('start_time', $startStr)
            ->where('end_time', $endStr);
    }

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

    public function template(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'template_id', 'id');
    }

    protected function casts(): array
    {
        return [
            'status' => ScheduleStatus::class,
            'type' => ScheduleType::class,
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

    /**
     * Summary line for global search results: time range and event title.
     * E.g. "Feb 17, 2026 6:30 PM – 8:30 PM · Methods (BSIT 3-1) – J. Garcia"
     */
    protected function searchResultSummary(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $timeRange = '';
                if ($this->start_time && $this->end_time) {
                    $timeRange = $this->start_time->format('M j, Y g:i A').' – '.$this->end_time->format('g:i A');
                }

                $event = $this->eventTitle;

                return $timeRange ? $timeRange.' · '.$event : $event;
            },
        );
    }
}
