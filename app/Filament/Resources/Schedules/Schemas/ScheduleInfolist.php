<?php

namespace App\Filament\Resources\Schedules\Schemas;

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
