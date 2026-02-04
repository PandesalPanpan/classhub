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

    public static function getGlobalSearchResults(string $search): Collection
    {
        [$parsedDate, $textSearch, $dateCandidate] = Schedule::extractDateAndTextFromSearch($search);

        $query = static::getGlobalSearchEloquentQuery();

        static::applyGlobalSearchAttributeConstraints($query, $textSearch);

        if ($parsedDate !== null) {
            $query->where(function (Builder $q) use ($parsedDate, $dateCandidate): void {
                Schedule::applyScheduleOverlapConstraint($q, $parsedDate, $dateCandidate);
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
