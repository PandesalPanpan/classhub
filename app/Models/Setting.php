<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Setting extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('setting');
    }

    protected $fillable = [
        'key_usage_check_percent',
        'handover_eligibility_window_minutes',
        'grace_period_minutes',
        'handover_enabled',
        'allow_past_schedule_requests',
        'allow_app_registration',
    ];

    protected function casts(): array
    {
        return [
            'key_usage_check_percent' => 'float',
            'handover_eligibility_window_minutes' => 'integer',
            'grace_period_minutes' => 'integer',
            'handover_enabled' => 'boolean',
            'allow_past_schedule_requests' => 'boolean',
            'allow_app_registration' => 'boolean',
        ];
    }

    public static function get(string $key, mixed $fallback = null): mixed
    {
        $defaults = static::defaults();
        $settings = static::cachedCurrent();

        if ($settings && array_key_exists($key, $settings)) {
            return $settings[$key];
        }

        if ($fallback !== null) {
            return $fallback;
        }

        return $defaults[$key] ?? null;
    }

    public static function defaults(): array
    {
        return [
            'key_usage_check_percent' => (float) config('classhub.schedule.key_usage_check_percent', 0.4),
            'handover_eligibility_window_minutes' => (int) config('classhub.schedule.handover_eligibility_window_minutes', 30),
            'grace_period_minutes' => (int) config('classhub.schedule.grace_period_minutes', 15),
            'handover_enabled' => (bool) config('classhub.schedule.handover_enabled', true),
            'allow_past_schedule_requests' => (bool) config('classhub.schedule.allow_past_schedule_requests', false),
            'allow_app_registration' => (bool) config('classhub.schedule.allow_app_registration', true),
        ];
    }

    public static function current(): self
    {
        $defaults = static::defaults();

        return static::query()->firstOrCreate(['id' => 1], $defaults);
    }

    public static function refreshCache(): void
    {
        Cache::forget('settings.current');
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::refreshCache());
        static::deleted(fn () => static::refreshCache());
    }

    /**
     * @return array<string, mixed>|null
     */
    protected static function cachedCurrent(): ?array
    {
        return Cache::remember('settings.current', now()->addMinutes(30), function (): ?array {
            $model = static::query()->first();

            if (! $model) {
                return null;
            }

            return [
                'key_usage_check_percent' => (float) $model->key_usage_check_percent,
                'handover_eligibility_window_minutes' => (int) $model->handover_eligibility_window_minutes,
                'grace_period_minutes' => (int) $model->grace_period_minutes,
                'handover_enabled' => (bool) $model->handover_enabled,
                'allow_past_schedule_requests' => (bool) $model->allow_past_schedule_requests,
                'allow_app_registration' => (bool) $model->allow_app_registration,
            ];
        });
    }
}
