<?php

namespace App\Models;

use App\KeyStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Key extends Model
{
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id', 'id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(KeyEvent::class)->orderByDesc('occurred_at');
    }

    protected function casts(): array
    {
        return [
            'status' => KeyStatus::class,
        ];
    }
}
