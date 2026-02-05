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

    /**
     * Whether the string looks like a date/time (contains digits or is a month name).
     * Used to avoid treating names like "Garcia" as dates when Carbon parses them.
     */
    protected static function isDateLike(string $candidate): bool
    {
        if (preg_match('/\d/', $candidate) === 1) {
            return true;
        }
        $lower = strtolower(trim($candidate));
        $months = ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december', 'jan', 'feb', 'mar', 'apr', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];

        return in_array($lower, $months, true);
    }

    /**
     * Extract a date/time from the search string and return remaining text.
     * Supports e.g. "Feb 17 6:30pm Garcia", "02/17/2026 18:00 Garcia", "6:30PM", "February".
     * Only treats a substring as a date if it looks date-like (digits or month name), so "Garcia" stays text-only.
     *
     * @return array{0: Carbon|null, 1: string, 2: string} [parsed date, text rest, date candidate string]
     */
    public static function extractDateAndTextFromSearch(string $search): array
    {
        $search = trim($search);
        if ($search === '') {
            return [null, '', ''];
        }

        $words = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);
        if ($words === []) {
            return [null, $search, ''];
        }

        for ($len = count($words); $len >= 1; $len--) {
            $candidate = implode(' ', array_slice($words, 0, $len));
            try {
                $dt = Carbon::parse($candidate);
                if ($dt->year >= 1970 && $dt->year <= 2100 && static::isDateLike($candidate)) {
                    $textRest = $len < count($words)
                        ? trim(implode(' ', array_slice($words, $len)))
                        : '';

                    return [$dt, $textRest, $candidate];
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return [null, $search, ''];
    }

    /**
     * Apply schedule overlap constraint: record overlaps the given datetime (point-in-time, whole day, or whole month).
     *
     * @param  string  $dateCandidate  The substring that was parsed as date (e.g. "February" → whole month)
     */
    public static function applyScheduleOverlapConstraint(Builder $query, Carbon $dt, string $dateCandidate = ''): void
    {
        $hasTime = $dt->hour !== 0 || $dt->minute !== 0 || $dt->second !== 0;

        if ($hasTime) {
            $query->where('start_time', '<=', $dt->format('Y-m-d H:i:s'))
                ->where('end_time', '>=', $dt->format('Y-m-d H:i:s'));
        } elseif ($dateCandidate !== '' && count(preg_split('/\s+/', trim($dateCandidate), -1, PREG_SPLIT_NO_EMPTY)) === 1) {
            $startOfMonth = $dt->copy()->startOfMonth()->format('Y-m-d H:i:s');
            $endOfMonth = $dt->copy()->endOfMonth()->format('Y-m-d H:i:s');
            $query->where('start_time', '<=', $endOfMonth)
                ->where('end_time', '>=', $startOfMonth);
        } else {
            $startOfDay = $dt->copy()->startOfDay()->format('Y-m-d H:i:s');
            $endOfDay = $dt->copy()->endOfDay()->format('Y-m-d H:i:s');
            $query->where('start_time', '<=', $endOfDay)
                ->where('end_time', '>=', $startOfDay);
        }
    }

    /**
     * Apply table search constraints: each word must match at least one of the searchable columns.
     * Matches: subject, program_year_section, instructor, status, remarks, room.room_number, requester.name, approver.name.
     */
    public static function applyTableSearchConstraint(Builder $query, string $search): void
    {
        $search = trim($search);
        if ($search === '') {
            return;
        }

        $words = array_filter(
            str_getcsv(preg_replace('/\s+/', ' ', $search), separator: ' ', escape: '\\'),
            fn (string $word): bool => filled($word),
        );
        if ($words === []) {
            return;
        }

        $model = new self;
        $table = $model->getTable();

        foreach ($words as $word) {
            $term = '%'.$word.'%';
            $termLower = strtolower($term);
            $query->where(function (Builder $q) use ($termLower, $table): void {
                $q->whereRaw('LOWER('.$table.'.subject) LIKE ?', [$termLower])
                    ->orWhereRaw('LOWER('.$table.'.program_year_section) LIKE ?', [$termLower])
                    ->orWhereRaw('LOWER('.$table.'.instructor) LIKE ?', [$termLower])
                    ->orWhereRaw('LOWER('.$table.'.remarks) LIKE ?', [$termLower])
                    ->orWhereRaw('LOWER('.$table.'.status) LIKE ?', [$termLower])
                    ->orWhereHas('room', fn (Builder $q) => $q->whereRaw('LOWER(room_number) LIKE ?', [$termLower]))
                    ->orWhereHas('requester', fn (Builder $q) => $q->whereRaw('LOWER(name) LIKE ?', [$termLower]))
                    ->orWhereHas('approver', fn (Builder $q) => $q->whereRaw('LOWER(name) LIKE ?', [$termLower]));
            });
        }
    }
}
