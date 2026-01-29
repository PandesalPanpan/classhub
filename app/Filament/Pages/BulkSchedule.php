<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Schemas\BulkScheduleForm;
use App\Models\Schedule;
use App\ScheduleStatus;
use App\ScheduleType;
use Carbon\Carbon;
use BackedEnum;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Concerns\InteractsWithHeaderActions;
use Filament\Pages\Page;
use Filament\Support\Enums\ActionSize;
use Illuminate\Support\Facades\Auth;
use App\Services\ScheduleOverlapChecker;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class BulkSchedule extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithFormActions;
    use InteractsWithHeaderActions;
    use InteractsWithActions;
    use HasPageShield;

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Bulk Schedule';

    protected static ?string $navigationLabel = 'Bulk Schedule';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected string $view = 'filament.pages.bulk-schedule';

    public ?array $data = [];

    public function getDescription(): string
    {
        return 'Create recurring schedules for classrooms (e.g., weekly classes)';
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form($form)
    {
        return $form
            ->schema(BulkScheduleForm::schema())
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('create')
                ->label('Create Bulk Schedule')
                ->submit('create'),
        ];
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }

    public function create(): void
    {
        $data = $this->form->getState();
        
        // Auto-fill requester_id and approver_id for admin
        if (Auth::check()) {
            $data['requester_id'] = Auth::id();
            $data['approver_id'] = Auth::id();
        }

        $schedules = $this->generateSchedules($data);
        
        if (empty($schedules)) {
            Notification::make()
                ->title('No schedules created')
                ->body('No valid schedule occurrences could be generated with the provided settings.')
                ->danger()
                ->send();
            return;
        }

        // Validate overlaps for all occurrences before creating any records
        foreach ($schedules as $scheduleData) {
            if (ScheduleOverlapChecker::hasOverlap(
                $scheduleData['room_id'],
                Carbon::parse($scheduleData['start_time']),
                Carbon::parse($scheduleData['end_time'])
            )) {
                Notification::make()
                    ->title('Schedule conflict')
                    ->body('One or more occurrences overlap with existing schedules for this room.')
                    ->danger()
                    ->send();
                return;
            }
        }

        // Create all schedules after validation passes
        foreach ($schedules as $scheduleData) {
            Schedule::create($scheduleData);
        }

        $count = count($schedules);
        
        Notification::make()
            ->title('Bulk schedule created')
            ->body("Successfully created {$count} schedule(s).")
            ->success()
            ->send();

        // Refresh calendar if it exists
        $this->dispatch('filament-fullcalendar--refresh');

        // Reset form
        $this->form->fill();
    }

    protected function generateSchedules(array $data): array
    {
        $schedules = [];
        $duration = (int) ($data['duration_minutes'] ?? 60);

        $daysOfWeek = collect($data['days_of_week'] ?? [])
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();

        if (empty($daysOfWeek)) {
            return [];
        }

        $startDate = Carbon::parse($data['semester_start_date'])->startOfDay();
        $endDate = Carbon::parse($data['semester_end_date'])->endOfDay();

        if ($startDate->gt($endDate)) {
            return [];
        }

        // Safety limit: prevent accidental huge generation (e.g., multi-year range)
        $maxCreated = 5000;
        $created = 0;

        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate) && $created < $maxCreated) {
            if (in_array($currentDate->dayOfWeek, $daysOfWeek, true)) {
                $startDateTime = Carbon::parse($currentDate->toDateString() . ' ' . ($data['start_time'] ?? '00:00:00'));
                $endDateTime = $startDateTime->copy()->addMinutes($duration);

                $schedules[] = [
                    'room_id' => $data['room_id'],
                    'subject' => $data['subject'],
                    'program_year_section' => $data['program_year_section'] ?? null,
                    'start_time' => $startDateTime,
                    'end_time' => $endDateTime,
                    'instructor' => $data['instructor'] ?? null,
                    'type' => $data['type'] ?? ScheduleType::Template->value,
                    'status' => $data['status'] ?? ScheduleStatus::Approved->value,
                    'requester_id' => $data['requester_id'] ?? Auth::id(),
                    'approver_id' => $data['approver_id'] ?? Auth::id(),
                    'remarks' => $data['remarks'] ?? null,
                ];

                $created++;
            }

            $currentDate->addDay();
        }

        return $schedules;
    }
}
