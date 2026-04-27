<?php

namespace App\Models;

use App\RoomType;
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
