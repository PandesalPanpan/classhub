<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class QueueJob extends Model
{
    protected $table = 'jobs';

    public $timestamps = false;

    protected $guarded = [];

    protected function jobClass(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $payload = json_decode($this->payload ?? '{}', true);

                return $payload['displayName'] ?? $payload['job'] ?? 'Unknown';
            },
        );
    }
}
