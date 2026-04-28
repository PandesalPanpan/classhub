<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configureForCreate(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->required()
                ->email()
                ->maxLength(255),
            TextInput::make('mobile_number')
                ->label('Mobile Number')
                ->tel()
                ->maxLength(32),
            TextInput::make('messenger_link')
                ->label('Messenger Link')
                ->url()
                ->maxLength(255)
                ->placeholder('https://m.me/username'),
            TextInput::make('password')
                ->password()
                ->dehydrated(fn ($state): bool => filled($state))
                ->dehydrateStateUsing(fn ($state): string => filled($state) ? Hash::make($state) : null)
                ->required(fn (string $operation): bool => $operation === 'create'),
            Select::make('roles')
                ->relationship('roles', 'name')
                ->required()
                ->options(Role::all()->pluck('name', 'id'))
                ->multiple(),
        ]);
    }

    public static function configureForEdit(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->required()
                ->email()
                ->maxLength(255)
                ->disabled(), // Maybe disable email on edit?
            TextInput::make('mobile_number')
                ->label('Mobile Number')
                ->tel()
                ->maxLength(32),
            TextInput::make('messenger_link')
                ->label('Messenger Link')
                ->url()
                ->maxLength(255)
                ->placeholder('https://m.me/username'),
            TextInput::make('password')
                ->password()
                ->dehydrated(fn ($state) => filled($state))
                ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                ->helperText('Leave empty to keep current password.'),
            Select::make('roles')
                ->relationship('roles', 'name')
                ->required()
                ->options(Role::all()->pluck('name', 'id'))
                ->multiple(),
        ]);
    }
}
