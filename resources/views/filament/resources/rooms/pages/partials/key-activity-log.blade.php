@php
    $key = $room->key;
    $activities = $key
        ? $key->activities()->latest()->limit(50)->get()
        : collect();
    $keyEvents = $key
        ? $key->events()->with('schedule')->limit(50)->get()
        : collect();
@endphp

@if (! $key)
    <p class="text-sm text-gray-600 dark:text-gray-300">No key is assigned to this room.</p>
@else
    @if ($keyEvents->isNotEmpty())
        <div class="mb-6 space-y-2">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Key events</h3>
            <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                @foreach ($keyEvents as $event)
                    <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-white/10 dark:bg-gray-900">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset bg-gray-50 text-gray-700 ring-gray-600/10 dark:bg-white/5 dark:text-gray-300 dark:ring-gray-500/40">
                                {{ strtoupper((string) $event->status) }}
                            </span>
                            @php
                                $source = (string) $event->source;
                                $sourceClass = match ($source) {
                                    'manual' => 'bg-amber-50 text-amber-900 ring-amber-700/30 dark:bg-amber-950/40 dark:text-amber-100 dark:ring-amber-500/30',
                                    'synthetic' => 'bg-purple-50 text-purple-900 ring-purple-700/30 dark:bg-purple-950/40 dark:text-purple-100 dark:ring-purple-500/30',
                                    'iot' => 'bg-slate-50 text-slate-800 ring-slate-600/20 dark:bg-slate-900/60 dark:text-slate-100 dark:ring-slate-500/40',
                                    default => 'bg-gray-50 text-gray-800 ring-gray-600/15 dark:bg-white/10 dark:text-gray-100',
                                };
                                $sourceLabel = match ($source) {
                                    'manual' => 'Manual (admin)',
                                    'synthetic' => 'Synthetic (handover)',
                                    'iot' => 'IoT',
                                    default => $source !== '' ? $source : '—',
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $sourceClass }}">
                                {{ $sourceLabel }}
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400 ml-auto shrink-0">
                                {{ $event->occurred_at?->format('M j, Y g:i A') }}
                            </span>
                        </div>
                        @if ($event->schedule)
                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                Schedule: {{ $event->schedule->subject ?? '—' }}
                                · #{{ $event->schedule_id }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($activities->isEmpty() && $keyEvents->isEmpty())
        <p class="text-sm text-gray-600 dark:text-gray-300">No key activity has been recorded yet.</p>
    @elseif ($activities->isNotEmpty())
        <h3 class="mb-2 text-sm font-semibold text-gray-900 dark:text-gray-100">Status history</h3>
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
@endif
