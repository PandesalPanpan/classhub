<?php

namespace App\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class LogViewer extends Page
{
    use HasPageShield;

    protected string $view = 'filament.pages.log-viewer';

    protected static \UnitEnum|string|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 95;

    protected static ?string $title = 'Log Viewer';

    protected static ?string $navigationLabel = 'Log Viewer';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public int $lineCount = 200;

    public string $logContent = '';

    public function mount(): void
    {
        $this->refreshLog();
    }

    public function updatedLineCount(): void
    {
        $this->refreshLog();
    }

    public function refreshLog(): void
    {
        $logPath = storage_path('logs/laravel.log');

        if (! file_exists($logPath)) {
            $this->logContent = '(Log file not found at '.$logPath.')';

            return;
        }

        $file = new \SplFileObject($logPath, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();
        $startLine = max(0, $totalLines - $this->lineCount);
        $lines = [];
        $file->seek($startLine);

        while (! $file->eof()) {
            $lines[] = $file->current();
            $file->next();
        }

        unset($file);

        $this->logContent = implode('', $lines);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refreshLog')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => $this->refreshLog()),
            Action::make('clearLog')
                ->label('Clear Log')
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Clear Laravel Log?')
                ->modalDescription('This will wipe the entire laravel.log file. This cannot be undone.')
                ->modalSubmitActionLabel('Yes, clear log')
                ->action(function () {
                    file_put_contents(storage_path('logs/laravel.log'), '');
                    $this->refreshLog();
                    Notification::make()->title('Log cleared')->success()->send();
                }),
        ];
    }
}
