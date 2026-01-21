<x-filament-panels::page>
    <form wire:submit="create">
        {{ $this->form }}

        <div class="mt-6 flex justify-end gap-3">
            <x-filament::button
                type="submit"
                color="primary"
                size="lg"
                wire:loading.attr="disabled"
                wire:target="create"
            >
                <span wire:loading.remove wire:target="create">Create Bulk Schedule</span>
                <span wire:loading wire:target="create">Creating…</span>
            </x-filament::button>
        </div>
    </form>

    <x-filament-actions::modals />
</x-filament-panels::page>
