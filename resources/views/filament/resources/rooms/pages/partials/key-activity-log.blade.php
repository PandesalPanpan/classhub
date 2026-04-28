@php
    $key = $room->key;
    $activities = $key
        ? $key->activities()->latest()->limit(50)->get()
        : collect();
@endphp

@if (! $key)
    <p class="text-sm text-gray-600 dark:text-gray-300">No key is assigned to this room.</p>
@elseif ($activities->isEmpty())
    <p class="text-sm text-gray-600 dark:text-gray-300">No key activity has been recorded yet.</p>
@else
    <div class="space-y-3 max-h-96 overflow-y-auto pr-1">
        @foreach ($activities as $activity)
            <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-white/10 dark:bg-gray-900">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                        {{ ucfirst($activity->event ?? 'updated') }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $activity->created_at?->format('M j, Y g:i A') }}
                    </p>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    By {{ $activity->causer?->name ?? 'System' }}
                </p>
                @if (! empty($activity->properties['attributes']))
                    <dl class="mt-2 grid grid-cols-1 gap-1 text-xs text-gray-700 dark:text-gray-300">
                        @foreach ($activity->properties['attributes'] as $field => $value)
                            <div class="flex gap-2">
                                <dt class="w-32 shrink-0 font-medium text-gray-500 dark:text-gray-400">{{ $field }}</dt>
                                <dd class="break-all">{{ is_scalar($value) ? $value : json_encode($value) }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </div>
        @endforeach
    </div>
@endif
