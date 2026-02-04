<?php

namespace App\Filament\Resources\Schedules;

use App\Filament\Resources\Schedules\Pages\CreateSchedule;
use App\Filament\Resources\Schedules\Pages\EditSchedule;
use App\Filament\Resources\Schedules\Pages\ListSchedules;
use App\Filament\Resources\Schedules\Pages\ViewSchedule;
use App\Filament\Resources\Schedules\Schemas\ScheduleForm;
use App\Filament\Resources\Schedules\Schemas\ScheduleInfolist;
use App\Filament\Resources\Schedules\Tables\SchedulesTable;
use App\Models\Schedule;
use App\ScheduleStatus;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ScheduleResource extends Resource
{
    protected static ?string $model = Schedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'subject';

    public static function form(Schema $schema): Schema
    {
        return ScheduleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ScheduleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SchedulesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSchedules::route('/'),
            'create' => CreateSchedule::route('/create'),
            'view' => ViewSchedule::route('/{record}'),
            'edit' => EditSchedule::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['subject', 'program_year_section', 'instructor', 'remarks'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->eventTitle;
    }

    /**
     * @return array<string, string>
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Schedule' => $record->searchResultSummary,
        ];
    }

    /**
     * Extract a date/time from the search string and return remaining text.
     * Supports e.g. "Feb 17 6:30pm Garcia", "02/17/2026 18:00 Garcia", "February".
     *
     * @return array{0: Carbon|null, 1: string, 2: string} [parsed date, text rest, date candidate string]
     */
    protected static function extractDateAndTextFromSearch(string $search): array
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
                if ($dt->year >= 1970 && $dt->year <= 2100) {
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
    protected static function applyScheduleOverlapConstraint(Builder $query, Carbon $dt, string $dateCandidate = ''): void
    {
        $hasTime = $dt->hour !== 0 || $dt->minute !== 0 || $dt->second !== 0;

        if ($hasTime) {
            $query->where('start_time', '<=', $dt->format('Y-m-d H:i:s'))
                ->where('end_time', '>=', $dt->format('Y-m-d H:i:s'));
        } elseif ($dateCandidate !== '' && str_word_count($dateCandidate) === 1) {
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

    public static function getGlobalSearchResults(string $search): Collection
    {
        [$parsedDate, $textSearch, $dateCandidate] = static::extractDateAndTextFromSearch($search);

        $query = static::getGlobalSearchEloquentQuery();

        static::applyGlobalSearchAttributeConstraints($query, $textSearch);

        if ($parsedDate !== null) {
            $query->where(function (Builder $q) use ($parsedDate, $dateCandidate): void {
                static::applyScheduleOverlapConstraint($q, $parsedDate, $dateCandidate);
            });
        }

        static::modifyGlobalSearchQuery($query, $search);

        return $query
            ->limit(static::getGlobalSearchResultsLimit())
            ->get()
            ->map(function (Model $record): ?GlobalSearchResult {
                $url = static::getGlobalSearchResultUrl($record);

                if (blank($url)) {
                    return null;
                }

                return new GlobalSearchResult(
                    title: static::getGlobalSearchResultTitle($record),
                    url: $url,
                    details: static::getGlobalSearchResultDetails($record),
                    actions: array_map(
                        fn (Action $action) => $action->hasRecord() ? $action : $action->record($record),
                        static::getGlobalSearchResultActions($record),
                    ),
                );
            })
            ->filter();
    }

    public static function getNavigationBadge(): ?string
    {
        return Schedule::where('status', ScheduleStatus::Pending)->count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Pending schedules';
    }
}
