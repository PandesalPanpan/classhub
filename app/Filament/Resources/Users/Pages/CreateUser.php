<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public function form(Schema $schema): Schema
    {
        return UserForm::configureForCreate($schema);
    }
}
