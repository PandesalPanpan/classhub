<?php

namespace App\Models;

use App\ScheduleStatus;
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
}
