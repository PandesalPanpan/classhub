<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Password;

class Register extends BaseRegister
{
    protected bool $isClassRepresentative = false;
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getClassRepresentativeFormComponent(),
                $this->getPolicyAcceptanceFormComponent(),
            ]);
    }

    protected function getClassRepresentativeFormComponent(): Component
    {
        // inline layout
        return Checkbox::make('is_class_representative')
            ->label(__('I am a Class Representative'))
            ->required()
            ->default(false);   
    }

    protected function getNameFormComponent(): Component
    {
        return TextInput::make('name')
            ->label(__('filament-panels::auth/pages/register.form.name.label'))
            ->required()
            ->maxLength(255)
            ->autofocus();
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('filament-panels::auth/pages/register.form.email.label'))
            ->email()
            ->required()
            ->maxLength(255)
            ->unique($this->getUserModel());
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::auth/pages/register.form.password.label'))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->rule(Password::default())
            ->showAllValidationMessages()
            ->dehydrateStateUsing(fn($state) => Hash::make($state))
            ->same('passwordConfirmation')
            ->validationAttribute(__('filament-panels::auth/pages/register.form.password.validation_attribute'));
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return TextInput::make('passwordConfirmation')
            ->label(__('filament-panels::auth/pages/register.form.password_confirmation.label'))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->dehydrated(false);
    }

    protected function getPolicyAcceptanceFormComponent(): Component
    {
        $policyUrl = route('policy');
        
        return Checkbox::make('policy_accepted')
            ->label(new HtmlString(
                __('I agree to the ') . 
                '<a href="' . $policyUrl . '" target="_blank" rel="noopener noreferrer" style="color: rgb(37, 99, 235); text-decoration: underline; font-weight: 500;">' . 
                __('CPE Room Utilization Terms & Conditions') . 
                '</a>'
            ))
            ->required()
            ->accepted() // This ensures the checkbox must be checked (value must be true/1)
            ->dehydrated(false); // Don't save this to the database
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeRegister(array $data): array
    {
        // Store the checkbox value before removing it
        $this->isClassRepresentative = (bool) ($data['is_class_representative'] ?? false);
        
        // Remove the field so it doesn't try to insert into a non-existent column
        unset($data['is_class_representative']);
        
        return $data;
    }
}
