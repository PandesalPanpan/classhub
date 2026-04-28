<?php

namespace App\Filament\Resources\Rooms\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class RoomInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Room Information')
                    ->schema([
                        TextEntry::make('room_number'),
                        TextEntry::make('room_type')
                            ->formatStateUsing(fn ($state) => Str::title(strtolower($state->value))),
                        TextEntry::make('capacity'),
                        IconEntry::make('is_active')
                            ->boolean(),
                        TextEntry::make('description')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),
                Section::make('Key Information')
                    ->schema([
                        TextEntry::make('key.slot_number')
                            ->label('Slot Number')
                            ->placeholder('-'),
                        TextEntry::make('key.status')
                            ->label('Key Status')
                            ->formatStateUsing(fn ($state): string => $state?->value ?? '-')
                            ->badge(),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),
                Section::make('Timestamps')
                    ->schema([
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
                    ])
                    ->collapsible(),
            ]);
    }
}
