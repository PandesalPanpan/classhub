<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;

class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getMobileNumberFormComponent(),
                $this->getMessengerLinkFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getCurrentPasswordFormComponent(),
            ]);
    }

    protected function getMobileNumberFormComponent(): Component
    {
        return TextInput::make('mobile_number')
            ->label('Mobile Number')
            ->tel()
            ->maxLength(32);
    }

    protected function getMessengerLinkFormComponent(): Component
    {
        return TextInput::make('messenger_link')
            ->label('Messenger Link')
            ->url()
            ->maxLength(255)
            ->placeholder('https://m.me/username');
    }
}
