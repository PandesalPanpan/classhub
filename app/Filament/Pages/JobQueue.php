<?php

namespace App\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class JobQueue extends Page
{
    use HasPageShield;

    protected static string $routePath = '/queue-monitor';

    protected static ?string $title = 'Queue Monitor';

    protected static ?string $navigationLabel = 'Queue Monitor';

    protected static \UnitEnum|string|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 90;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected string $view = 'filament.pages.job-queue';
}
