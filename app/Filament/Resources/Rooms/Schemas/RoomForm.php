<?php

namespace App\Filament\Resources\Rooms\Schemas;

use App\KeyStatus;
use App\RoomType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RoomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Room Information')
                    ->schema([
                        TextInput::make('room_number')
                            ->required(),
                        Select::make('room_type')
                            ->options(RoomType::class)
                            ->required(),
                        TextInput::make('capacity')
                            ->required(),
                        Toggle::make('is_active')
                            ->required(),
                        TextInput::make('description')
                            ->columnSpanFull(),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ]),
                Section::make('Key Configuration')
                    ->schema([
                        Fieldset::make('Key')
                            ->relationship('key')
                            ->schema([
                                TextInput::make('slot_number')
                                    ->required(),
                                Select::make('status')
                                    ->required()
                                    ->options(KeyStatus::class)
                                    ->default(KeyStatus::Disabled),
                            ])
                            ->columns([
                                'default' => 1,
                                'md' => 2,
                            ]),
                    ])
                    ->columns(1),
            ]);
    }
}
