<?php

namespace App\Models;

use App\KeyStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Key extends Model
{
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id', 'id');
    }

    protected function casts(): array
    {
        return [
            'status' => KeyStatus::class,
        ];
    }
}
