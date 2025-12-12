<x-filament-panels::page>
    {{-- Page content --}}
    @livewire(\App\Livewire\CalendarWidget::class)
    {{ $this->table }}
</x-filament-panels::page>
