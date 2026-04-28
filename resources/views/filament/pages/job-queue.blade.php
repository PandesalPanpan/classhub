<x-filament-panels::page>
    <div class="space-y-6">
        @livewire(\App\Filament\Widgets\ActiveJobsQueueWidget::class)

        @livewire(\App\Filament\Widgets\FailedJobsQueueWidget::class)
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>

