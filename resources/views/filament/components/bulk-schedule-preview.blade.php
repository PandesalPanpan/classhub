@php
    $dayNames = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    $selectedDays = collect($data['days_of_week'] ?? [])
        ->map(fn ($day) => $dayNames[$day] ?? '')
        ->filter()
        ->join(', ');
@endphp

@if(empty($preview['schedules']))
    <p class="fi-description-text text-gray-500 dark:text-gray-400">Fill in the schedule details above to see a preview.</p>
@else
    <div class="space-y-6">
        {{-- Summary Section --}}
        <div class="fi-section rounded-xl border border-gray-200 bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:border-gray-700 dark:bg-gray-800 dark:ring-white/10">
            <h4 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white mb-4 pb-3 border-b border-gray-200 dark:border-gray-700">
                Summary
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                @if($roomName)
                    <div class="flex flex-col">
                        <span class="fi-description-text font-medium text-gray-500 dark:text-gray-400 mb-1">Room</span>
                        <span class="text-gray-950 dark:text-white">{{ $roomName }}</span>
                    </div>
                @endif

                @if($data['subject'] ?? null)
                    <div class="flex flex-col">
                        <span class="fi-description-text font-medium text-gray-500 dark:text-gray-400 mb-1">Subject / Purpose</span>
                        <span class="text-gray-950 dark:text-white">{{ $data['subject'] }}</span>
                    </div>
                @endif

                @if($data['program_year_section'] ?? null)
                    <div class="flex flex-col">
                        <span class="fi-description-text font-medium text-gray-500 dark:text-gray-400 mb-1">Program Year & Section</span>
                        <span class="text-gray-950 dark:text-white">{{ $data['program_year_section'] }}</span>
                    </div>
                @endif

                <div class="flex flex-col">
                    <span class="fi-description-text font-medium text-gray-500 dark:text-gray-400 mb-1">Days</span>
                    <span class="text-gray-950 dark:text-white">{{ $selectedDays }}</span>
                </div>

                <div class="flex flex-col">
                    <span class="fi-description-text font-medium text-gray-500 dark:text-gray-400 mb-1">Time</span>
                    <span class="text-gray-950 dark:text-white">
                        {{ \Illuminate\Support\Carbon::parse($data['start_time'])->format('g:i A') }} - 
                        {{ \Illuminate\Support\Carbon::parse($data['start_time'])->addMinutes($data['duration_minutes'])->format('g:i A') }}
                    </span>
                </div>

                <div class="flex flex-col md:col-span-2">
                    <span class="fi-description-text font-medium text-gray-500 dark:text-gray-400 mb-1">Date Range</span>
                    <span class="text-gray-950 dark:text-white">
                        {{ \Illuminate\Support\Carbon::parse($data['semester_start_date'])->format('M j, Y') }} to 
                        {{ \Illuminate\Support\Carbon::parse($data['semester_end_date'])->format('M j, Y') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Sample Schedules Table --}}
        @if(!empty($preview['schedules']))
            <div class="fi-section rounded-xl border border-gray-200 bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:border-gray-700 dark:bg-gray-800 dark:ring-white/10">
                <h4 class="fi-section-header-heading text-base font-semibold leading-6 text-gray-950 dark:text-white mb-4 pb-3 border-b border-gray-200 dark:border-gray-700">
                    Schedules that will be generated ({{ number_format($preview['total']) }})
                </h4>
                <div class="overflow-x-auto -mx-6 px-6">
                    <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-gray-700">
                        <thead class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr class="bg-gray-50 dark:bg-gray-800/50">
                                <th class="fi-ta-header-cell px-4 py-3 text-start text-xs font-semibold text-gray-950 dark:text-white sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                    Date
                                </th>
                                <th class="fi-ta-header-cell px-4 py-3 text-start text-xs font-semibold text-gray-950 dark:text-white sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                    Day
                                </th>
                                <th class="fi-ta-header-cell px-4 py-3 text-start text-xs font-semibold text-gray-950 dark:text-white sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                    Time
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-gray-700">
                            @foreach($preview['schedules'] as $schedule)
                                <tr class="fi-ta-row transition duration-75 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                                        <div class="px-4 py-3">
                                            <span class="text-sm font-medium text-gray-950 dark:text-white">
                                                {{ $schedule['date'] }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                                        <div class="px-4 py-3">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $schedule['day'] }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3">
                                        <div class="px-4 py-3">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $schedule['time'] }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endif
