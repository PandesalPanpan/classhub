<?php

namespace App\Models;

use App\KeyStatus;
use App\RoomType;
use App\ScheduleStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Room extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['room_number', 'is_active', 'room_type', 'capacity', 'description'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('room');
    }

    public function key(): HasOne
    {
        return $this->hasOne(Key::class, 'room_id', 'id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'room_id', 'id');
    }

    public function getCurrentKeySchedule(): ?Schedule
    {
        if ($this->relationLoaded('currentKeyScheduleLookup')) {
            return $this->getRelation('currentKeyScheduleLookup');
        }

        if (! $this->relationLoaded('key')) {
            $this->load('key');
        }

        if (! $this->key) {
            $this->setRelation('currentKeyScheduleLookup', null);

            return null;
        }

        $now = now();
        $maxStalenessMinutes = (int) Setting::get('grace_period_minutes', 30)
            + (int) Setting::get('handover_eligibility_window_minutes', 60);
        $staleCutoff = $now->copy()->subMinutes($maxStalenessMinutes);

        $activeSchedule = $this->schedules()
            ->where('status', ScheduleStatus::Approved)
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->with('requester')
            ->orderBy('start_time')
            ->first();

        if ($activeSchedule) {
            $this->setRelation('currentKeyScheduleLookup', $activeSchedule);

            return $activeSchedule;
        }

        if (in_array($this->key->status, [KeyStatus::Used, KeyStatus::HandedOver, KeyStatus::Missing], true)) {
            $latestUsedEvent = KeyEvent::query()
                ->where('key_id', $this->key->id)
                ->whereNotNull('schedule_id')
                ->where('status', KeyStatus::Used->value)
                ->whereHas('schedule', function ($query) use ($staleCutoff) {
                    $query->where('end_time', '>=', $staleCutoff);
                })
                ->with('schedule.requester')
                ->orderByDesc('occurred_at')
                ->first();

            $schedule = $latestUsedEvent?->schedule;
            $this->setRelation('currentKeyScheduleLookup', $schedule);

            return $schedule;
        }

        $this->setRelation('currentKeyScheduleLookup', null);

        return null;
    }

    protected function casts(): array
    {
        return [
            'room_type' => RoomType::class,
        ];
    }

    protected function roomFullLabel(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                return $this->room_number.' - '.Str::title(strtolower($this->room_type->value));
            },
        );
    }
}
