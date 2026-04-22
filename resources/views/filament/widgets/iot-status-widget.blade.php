<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between gap-4">
            <div class="space-y-1">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">IoT Room Key Gateway</p>
                <div class="flex items-center gap-2">
                    <span @class([
                        'inline-block h-2.5 w-2.5 rounded-full',
                        'bg-success-500' => $isActive,
                        'bg-danger-500' => ! $isActive,
                    ])></span>
                    <p class="text-base font-semibold">
                        {{ $isActive ? 'Active' : 'Inactive' }}
                    </p>
                </div>
            </div>

            <div class="text-right text-sm text-gray-600 dark:text-gray-300">
                @if ($lastSeen)
                    <p>Last seen {{ $lastSeen }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $lastSeenAt }}</p>
                @else
                    <p>Never seen</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">No successful IoT request yet</p>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
