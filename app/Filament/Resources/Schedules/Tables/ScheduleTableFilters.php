<?php

namespace App\Filament\Resources\Schedules\Tables;

use App\ScheduleStatus;
use App\Models\Schedule;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class ScheduleTableFilters
{
    /**
     * Return schedule table filters. Use includeRequester: false on pages
     * that are already scoped by requester (e.g. RequestSchedule).
     *
     * @return array<Filter|SelectFilter>
     */
    public static function filters(bool $includeRequester = true, bool $defaultPendingStatus = true): array
    {
        $filters = [
            static::statusFilter($defaultPendingStatus),
            static::roomFilter(),
            static::dateRangeFilter(),
            static::instructorFilter(),
            static::subjectFilter(),
            static::programYearSectionFilter(),
        ];

        if ($includeRequester) {
            $filters[] = static::requesterFilter();
        }

        return $filters;
    }

    public static function statusFilter(bool $defaultPendingStatus = true): SelectFilter
    {
        return SelectFilter::make('status')
        ->options(ScheduleStatus::class)
        ->multiple()
        ->label('Status')
        ->default($defaultPendingStatus ? null : ScheduleStatus::Pending->value);
    }

    public static function roomFilter(): SelectFilter
    {
        return SelectFilter::make('room_id')
            ->label('Room')
            ->relationship(
                'room',
                'room_number',
                modifyQueryUsing: fn (Builder $query) => $query->orderBy('room_number'),
            )
            ->searchable()
            ->preload();
    }

    public static function dateRangeFilter(): Filter
    {
        return Filter::make('schedule_date_range')
            ->label('Schedule date & time range')
            ->schema([
                DateTimePicker::make('from')
                    ->label('From')
                    ->seconds(false),
                DateTimePicker::make('to')
                    ->label('To')
                    ->seconds(false),
            ])
            ->query(function (Builder $query, array $data): void {
                $from = $data['from'] ?? null;
                $to = $data['to'] ?? null;

                if (filled($from)) {
                    $query->where('end_time', '>=', Carbon::parse($from)->format('Y-m-d H:i:s'));
                }

                if (filled($to)) {
                    $query->where('start_time', '<=', Carbon::parse($to)->format('Y-m-d H:i:s'));
                }
            })
            ->indicateUsing(function (array $data): array {
                $indicators = [];
                $format = 'M j, Y g:i A';

                if (filled($data['from'] ?? null)) {
                    $indicators[] = Indicator::make('From '.Carbon::parse($data['from'])->format($format))
                        ->removeField('from');
                }

                if (filled($data['to'] ?? null)) {
                    $indicators[] = Indicator::make('To '.Carbon::parse($data['to'])->format($format))
                        ->removeField('to');
                }

                return $indicators;
            });
    }

    public static function instructorFilter(): SelectFilter
    {
        return SelectFilter::make('instructor')
            ->label('Instructor')
            ->options(fn (): array => Schedule::query()
                ->distinct()
                ->whereNotNull('instructor')
                ->where('instructor', '!=', '')
                ->orderBy('instructor')
                ->pluck('instructor', 'instructor')
                ->all())
            ->searchable();
    }

    public static function subjectFilter(): SelectFilter
    {
        return SelectFilter::make('subject')
            ->label('Subject')
            ->options(fn (): array => Schedule::query()
                ->distinct()
                ->orderBy('subject')
                ->pluck('subject', 'subject')
                ->all())
            ->searchable();
    }

    public static function programYearSectionFilter(): SelectFilter
    {
        return SelectFilter::make('program_year_section')
            ->label('Program year & section')
            ->options(fn (): array => Schedule::query()
                ->distinct()
                ->whereNotNull('program_year_section')
                ->where('program_year_section', '!=', '')
                ->orderBy('program_year_section')
                ->pluck('program_year_section', 'program_year_section')
                ->all())
            ->searchable();
    }

    public static function requesterFilter(): SelectFilter
    {
        return SelectFilter::make('requester_id')
            ->label('Requester')
            ->relationship(
                'requester',
                'name',
                modifyQueryUsing: fn (Builder $query) => $query
                    ->whereHas('requestedSchedules')
                    ->orderBy('name'),
            )
            ->searchable()
            ->preload();
    }
}
