<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\MarkdownEditor;
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

    protected static \UnitEnum|string|null $navigationGroup = 'System';

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
                Section::make('Key Verification')
                    ->description('Configure when and how the system verifies key usage during schedules.')
                    ->schema([
                        TextInput::make('key_usage_check_percent')
                            ->label('Key Usage Check Percent')
                            ->numeric()
                            ->minValue(0.1)
                            ->maxValue(0.9)
                            ->step(0.01)
                            ->required()
                            ->helperText('Value between 0.10 and 0.90. Fraction of schedule duration before the key usage verification job runs. E.g., 0.40 = 40% into a 1-hour class = 24 minutes.'),
                        TextInput::make('early_key_pickup_minutes')
                            ->label('Early Key Pickup Window (minutes)')
                            ->numeric()
                            ->integer()
                            ->minValue(5)
                            ->maxValue(60)
                            ->required()
                            ->helperText('How many minutes before scheduled start time to accept a USED key event as valid usage.'),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),
                Section::make('Handover & Grace Period')
                    ->description('Configure post-class handover window and grace period checks.')
                    ->schema([
                        TextInput::make('handover_eligibility_window_minutes')
                            ->label('Handover Eligibility Window (minutes)')
                            ->numeric()
                            ->integer()
                            ->minValue(5)
                            ->maxValue(120)
                            ->required()
                            ->helperText('After class end, this defines how many minutes ahead to look for the next approved schedule in the same room.'),
                        TextInput::make('grace_period_minutes')
                            ->label('Grace Period (minutes)')
                            ->numeric()
                            ->integer()
                            ->minValue(5)
                            ->maxValue(60)
                            ->required()
                            ->helperText('Minutes after class end before checking for key return. Must be less than or equal to the handover window.'),
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
                Section::make('Policy Page')
                    ->description('Publish the public policy page content shown on /policy using Markdown.')
                    ->schema([
                        MarkdownEditor::make('policy_content')
                            ->label('Policy Content')
                            ->required()
                            ->columnSpanFull()
                            ->helperText('Supports headings, lists, links, and emphasis.'),
                    ]),
                Section::make('Reservation & Policy Rules')
                    ->description('Content shown in the "View Reservation & Policy Rules" modal for schedule requests.')
                    ->schema([
                        MarkdownEditor::make('reservation_rules_content')
                            ->label('Reservation Rules Content')
                            ->required()
                            ->columnSpanFull()
                            ->helperText('Supports headings, lists, links, and emphasis.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $validator = Validator::make($data, [
            'key_usage_check_percent' => ['required', 'numeric', 'between:0.1,0.9'],
            'early_key_pickup_minutes' => ['required', 'integer', 'between:5,60'],
            'handover_eligibility_window_minutes' => ['required', 'integer', 'between:5,120'],
            'grace_period_minutes' => ['required', 'integer', 'between:5,60', 'lte:handover_eligibility_window_minutes'],
            'handover_enabled' => ['required', 'boolean'],
            'allow_past_schedule_requests' => ['required', 'boolean'],
            'allow_app_registration' => ['required', 'boolean'],
            'policy_content' => ['required', 'string'],
            'reservation_rules_content' => ['required', 'string'],
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

        $settings = Setting::current();
        if (($payload['policy_content'] ?? null) !== $settings->policy_content) {
            $payload['policy_updated_at'] = now();
        }

        $settings->update($payload);
        Setting::refreshCache();

        Notification::make()
            ->title('Settings saved')
            ->body('System settings have been updated successfully.')
            ->success()
            ->send();
    }
}
