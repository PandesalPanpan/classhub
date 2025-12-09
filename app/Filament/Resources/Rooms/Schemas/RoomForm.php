<?php

namespace App\Filament\Resources\Rooms\Schemas;

use App\KeyStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Schema;

class RoomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('room_number')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('description'),
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
                    ->columns(2),
            ]);
    }
}
