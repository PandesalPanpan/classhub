<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Validator;

class ManageSettings extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected static ?string $title = 'System Settings';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 100;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected string $view = 'filament.pages.manage-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(Setting::current()->toArray());
    }

    public function form($form)
    {
        return $form
            ->schema([
                Section::make('Timing Configuration')
                    ->description('Update scheduling and handover timing behavior used by jobs and validation rules.')
                    ->schema([
                        TextInput::make('key_usage_check_percent')
                            ->label('Key Usage Check Percent')
                            ->numeric()
                            ->minValue(0.1)
                            ->maxValue(0.9)
                            ->step(0.01)
                            ->required()
                            ->helperText('Value between 0.10 and 0.90. For 30-minute schedules, this check should happen after the grace period to avoid timing conflicts.'),
                        TextInput::make('handover_eligibility_window_minutes')
                            ->label('Handover Eligibility Window (minutes)')
                            ->numeric()
                            ->integer()
                            ->minValue(5)
                            ->maxValue(120)
                            ->required(),
                        TextInput::make('grace_period_minutes')
                            ->label('Grace Period (minutes)')
                            ->numeric()
                            ->integer()
                            ->minValue(5)
                            ->maxValue(60)
                            ->required()
                            ->helperText('If grace period is too high relative to key usage check percent, verification can run before handover resolution. Runtime deferral handles this, but lower grace is recommended.'),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),
                Section::make('Feature Toggles')
                    ->description('Enable or disable optional schedule and portal behaviors.')
                    ->schema([
                        Toggle::make('handover_enabled')
                            ->label('Enable handover flow')
                            ->required(),
                        Toggle::make('allow_past_schedule_requests')
                            ->label('Allow past schedule requests')
                            ->required(),
                        Toggle::make('allow_app_registration')
                            ->label('Allow app panel self-registration')
                            ->required(),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 3,
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $validator = Validator::make($data, [
            'key_usage_check_percent' => ['required', 'numeric', 'between:0.1,0.9'],
            'handover_eligibility_window_minutes' => ['required', 'integer', 'between:5,120'],
            'grace_period_minutes' => ['required', 'integer', 'between:5,60', 'lte:handover_eligibility_window_minutes'],
            'handover_enabled' => ['required', 'boolean'],
            'allow_past_schedule_requests' => ['required', 'boolean'],
            'allow_app_registration' => ['required', 'boolean'],
        ], [
            'grace_period_minutes.lte' => 'The grace period must be less than or equal to the handover eligibility window.',
        ]);

        if ($validator->fails()) {
            $this->addError('data', $validator->errors()->first());

            Notification::make()
                ->title('Settings could not be saved')
                ->body($validator->errors()->first())
                ->danger()
                ->send();

            return;
        }

        $payload = $validator->validated();

        $minimumSchedulableDurationMinutes = 30;
        $verifyPointMinutes = $minimumSchedulableDurationMinutes * (float) $payload['key_usage_check_percent'];
        if ($verifyPointMinutes <= (int) $payload['grace_period_minutes']) {
            Notification::make()
                ->title('Potential timing conflict detected')
                ->body('For 30-minute schedules, key verification may run before handover grace ends. Jobs will defer automatically, but consider lowering grace period or increasing check percent.')
                ->warning()
                ->send();
        }

        Setting::current()->update($payload);
        Setting::refreshCache();

        Notification::make()
            ->title('Settings saved')
            ->body('System settings have been updated successfully.')
            ->success()
            ->send();
    }
}
