@props(['results' => [], 'panelId' => null])

@php
    $showActionColumn = ($panelId ?? null) === 'app'
        && auth()->check()
        && auth()->user()->can('Create:Schedule');
@endphp

@if(empty($results))
    <p class="fi-description-text text-gray-500 dark:text-gray-400">
        Enter date, time, and duration, then click Find rooms.
    </p>
@else
    <div class="min-w-0 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm ring-1 ring-gray-950/5 dark:border-gray-700 dark:bg-gray-800 dark:ring-white/10">
        <table class="fi-ta-table w-full table-fixed divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr class="bg-gray-50 dark:bg-gray-800/50">
                    <th class="fi-ta-header-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white">
                        Room
                    </th>
                    <th class="fi-ta-header-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white">
                        Status
                    </th>
                    @if($showActionColumn)
                        <th class="fi-ta-header-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white">
                            Action
                        </th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($results as $row)
                    @php
                        $room = $row['room'] ?? null;
                        $available = $row['available'] ?? false;
                    @endphp
                    @if($room)
                        <tr class="fi-ta-row">
                            <td class="fi-ta-cell p-3 text-sm text-gray-950 dark:text-white">
                                {{ $room->room_full_label ?? $room->room_number }}
                            </td>
                            <td class="fi-ta-cell p-3">
                                @if($available && !($row['template_conflict'] ?? false))
                                    <x-filament::badge color="success" size="sm">
                                        Available
                                    </x-filament::badge>
                                @elseif($available && ($row['template_conflict'] ?? false))
                                    <x-filament::badge color="warning" size="sm">
                                        Template Only
                                    </x-filament::badge>
                                @else
                                    <x-filament::badge color="danger" size="sm">
                                        Conflict
                                    </x-filament::badge>
                                @endif
                            </td>
                            @if($showActionColumn)
                                <td class="fi-ta-cell p-3">
                                    @if($available)
                                        <x-filament::button
                                            size="sm"
                                            wire:click="openCreateRequestFromFindRooms({{ $room->id }})"
                                        >
                                            {{ ($row['template_conflict'] ?? false) ? 'Request Override' : 'Create Request' }}
                                        </x-filament::button>
                                    @else
                                        <span class="text-sm text-gray-500 dark:text-gray-400">—</span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
@endif
