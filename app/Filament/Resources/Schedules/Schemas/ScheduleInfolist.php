<?php

namespace App\Filament\Resources\Schedules\Schemas;

use App\Models\Schedule;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ScheduleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Schedule Details')
                    ->schema([
                        TextEntry::make('room.room_number')
                            ->label('Room')
                            ->placeholder('-'),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('subject')
                            ->label('Subject / Purpose'),
                        TextEntry::make('program_year_section')
                            ->label('Program Year & Section')
                            ->placeholder('-'),
                        TextEntry::make('instructor')
                            ->placeholder('-'),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),
                Section::make('People')
                    ->schema([
                        TextEntry::make('requester.name')
                            ->label('Requester')
                            ->placeholder('-'),
                        TextEntry::make('requester.mobile_number')
                            ->label('Mobile')
                            ->placeholder('—')
                            ->copyable(),
                        TextEntry::make('requester.messenger_link')
                            ->label('Messenger')
                            ->placeholder('—')
                            ->url(fn (?string $state): ?string => filled($state) ? $state : null, shouldOpenInNewTab: true),
                        TextEntry::make('approver.name')
                            ->label('Approver')
                            ->placeholder('-'),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),
                Section::make('Time & Date')
                    ->schema([
                        TextEntry::make('start_time')
                            ->dateTime(),
                        TextEntry::make('end_time')
                            ->dateTime(),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),
                Section::make('Key Events')
                    ->schema([
                        RepeatableEntry::make('keyEvents')
                            ->schema([
                                TextEntry::make('status')
                                    ->badge(),
                                TextEntry::make('source')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'manual' => 'Manual',
                                        'synthetic' => 'Synthetic',
                                        'iot' => 'IoT',
                                        default => $state ?? '—',
                                    })
                                    ->color(fn (?string $state): string => match ($state) {
                                        'manual' => 'info',
                                        'synthetic' => 'warning',
                                        'iot' => 'gray',
                                        default => 'gray',
                                    }),
                                TextEntry::make('occurred_at')
                                    ->dateTime('M j, Y g:iA'),
                            ])
                            ->columns(3),
                    ])
                    ->visible(fn (Schedule $record) => $record->keyEvents->isNotEmpty())
                    ->collapsible()
                    ->collapsed(),
                Section::make('Additional Information')
                    ->schema([
                        TextEntry::make('remarks')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('created_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->dateTime()
                            ->placeholder('-'),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),
            ]);
    }
}
