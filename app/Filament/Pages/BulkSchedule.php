<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Schemas\BulkScheduleForm;
use App\Models\Schedule;
use App\ScheduleStatus;
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

class BulkSchedule extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithFormActions;
    use InteractsWithHeaderActions;
    use InteractsWithActions;

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

        // If ending on a specific date, ensure end_date exists
        if (($data['recurrence_end_type'] ?? null) === 'on') {
            $start = Carbon::parse($data['start_time']);
            $data['end_date'] = $data['end_date'] ?? $start->format('Y-m-d H:i:s');
        }

        // Ensure first occurrence end_time respects duration if provided
        if (isset($data['start_time']) && isset($data['duration_minutes'])) {
            $start = Carbon::parse($data['start_time']);
            $data['end_time'] = $start->copy()->addMinutes($data['duration_minutes']);
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

        // Create all schedules
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
        $startTime = Carbon::parse($data['start_time']);
        // Prefer provided duration; otherwise compute from end_time
        if (isset($data['duration_minutes'])) {
            $duration = (int) $data['duration_minutes'];
        } else {
            $endTime = Carbon::parse($data['end_time']);
            $duration = $startTime->diffInMinutes($endTime);
        }
        
        $recurrenceType = $data['recurrence_type'];
        $recurrenceEndType = $data['recurrence_end_type'];
        
        // Determine end date
        $endDate = null;
        if ($recurrenceEndType === 'on') {
            $endDate = Carbon::parse($data['end_date']);
        } elseif ($recurrenceEndType === 'after') {
            $occurrences = (int) ($data['occurrences'] ?? 12);
        }

        $currentDate = $startTime->copy();
        $count = 0;
        $maxOccurrences = $recurrenceEndType === 'after' ? $occurrences : 1000; // Safety limit

        while ($count < $maxOccurrences) {
            // Check if we've exceeded the end date
            if ($recurrenceEndType === 'on' && $currentDate->gt($endDate)) {
                break;
            }

            // Create schedule for this occurrence
            $scheduleData = [
                'room_id' => $data['room_id'],
                'title' => $data['title'],
                'block' => $data['block'] ?? null,
                'start_time' => $currentDate->copy(),
                'end_time' => $currentDate->copy()->addMinutes($duration),
                'status' => $data['status'] ?? ScheduleStatus::Approved->value,
                'requester_id' => $data['requester_id'] ?? Auth::id(),
                'approver_id' => $data['approver_id'] ?? Auth::id(),
                'remarks' => $data['remarks'] ?? null,
            ];

            $schedules[] = $scheduleData;
            $count++;

            // Move to next occurrence based on recurrence type
            switch ($recurrenceType) {
                case 'daily':
                    $currentDate->addDay();
                    break;
                case 'weekly':
                    $currentDate->addWeek();
                    break;
                case 'monthly':
                    $currentDate->addMonth();
                    break;
            }
        }

        return $schedules;
    }
}
