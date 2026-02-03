@props(['schedules' => collect()])

<div class="grid gap-y-4">
    @foreach($schedules as $schedule)
        @php
            $id = data_get($schedule, 'id');
            $subject = data_get($schedule, 'subject');
            $requesterName = data_get($schedule, 'requester.name') ?? 'Requester #' . data_get($schedule, 'requester_id');
            $timeRange = null;
            if ($start = data_get($schedule, 'start_time')) {
                $startCarbon = is_object($start) ? $start : \Carbon\Carbon::parse($start);
                $end = data_get($schedule, 'end_time');
                $endCarbon = $end ? (is_object($end) ? $end : \Carbon\Carbon::parse($end)) : null;
                $timeRange = $startCarbon->format('M j, g:i A') . ($endCarbon ? ' – ' . $endCarbon->format('g:i A') : '');
            }
        @endphp
        <x-filament::section compact>
            <div class="flex items-center gap-3">
                <div class="fi-section-header-text-ctn grid flex-1 gap-y-1 min-w-0">
                    <p class="fi-section-header-heading truncate">
                        {{ $subject ?: '—' }}
                    </p>
                    <p class="fi-section-header-description">
                        {{ $requesterName }}
                        @if($timeRange)
                            · {{ $timeRange }}
                        @endif
                    </p>
                </div>
                <x-filament::button
                    color="success"
                    size="sm"
                    tag="button"
                    type="button"
                    wire:click="approveMatchingSchedule({{ $id }})"
                >
                    Approve
                </x-filament::button>
            </div>
        </x-filament::section>
    @endforeach
</div>
