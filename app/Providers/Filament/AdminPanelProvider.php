<?php

namespace App\Providers\Filament;

use App\Filament\Pages\BulkSchedule;
use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\Rooms\RoomResource;
use App\Filament\Resources\Schedules\ScheduleResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\IotStatusWidget;
use App\Livewire\CalendarWidget;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;
use DiogoGPinto\AuthUIEnhancer\AuthUIEnhancerPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->profile()
            ->topNavigation()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            // ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            // ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
                BulkSchedule::class,
                ManageSettings::class,
            ])
            ->resources([
                ScheduleResource::class,
                RoomResource::class,
                UserResource::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                IotStatusWidget::class,
                CalendarWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins(
                [
                    FilamentFullCalendarPlugin::make()
                        ->schedulerLicenseKey('GPL-My-Project-Is-Open-Source')
                        ->selectable(true)
                        ->timezone(config('app.timezone'))
                        ->plugins([
                            'dayGrid',
                            'timeGrid',
                            'interaction',
                            'list',
                            'resource',
                            'resourceTimeline',
                        ])
                        ->config([
                            'initialView' => 'timeGridWeek',
                        ]),
                    FilamentShieldPlugin::make(),
                    AuthUIEnhancerPlugin::make()
                        ->formPanelPosition('right')
                        ->formPanelWidth('40%')
                        ->emptyPanelBackgroundImageUrl('https://i.imgur.com/2xQUt6D.jpeg')
                ],
            )
            ->databaseNotifications()
            ->databaseNotificationsPolling(null);
    }
}
