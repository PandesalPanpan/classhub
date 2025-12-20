<?php

namespace App\Models;

use App\RoomType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
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
}
