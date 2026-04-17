<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleHandover extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'previous_confirmed_at' => 'datetime',
            'next_confirmed_at' => 'datetime',
            'previous_disputed_at' => 'datetime',
            'next_disputed_at' => 'datetime',
            'resolution_deadline_at' => 'datetime',
            'resolution_finalized_at' => 'datetime',
        ];
    }

    public function previousSchedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'previous_schedule_id');
    }

    public function nextSchedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'next_schedule_id');
    }

    public function isBothConfirmed(): bool
    {
        return $this->previous_confirmed_at !== null && $this->next_confirmed_at !== null;
    }

    public function isPendingResolution(): bool
    {
        return $this->resolution_finalized_at === null;
    }

    public function hasAnyDispute(): bool
    {
        return $this->previous_disputed_at !== null || $this->next_disputed_at !== null;
    }

    public function markFinalized(): void
    {
        $this->update(['resolution_finalized_at' => now()]);
    }
}
