<?php

namespace App\Filament\Pages\Auth;

use App\Models\Setting;
use DiogoGPinto\AuthUIEnhancer\Pages\Auth\AuthUiEnhancerLogin as BaseLogin;
use Filament\Facades\Filament;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class Login extends BaseLogin
{
    public function getSubheading(): string|Htmlable|null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return __('filament-panels::auth/pages/login.multi_factor.subheading');
        }

        if (! Filament::hasRegistration() || ! (bool) Setting::get('allow_app_registration')) {
            return null;
        }

        return new HtmlString(__('filament-panels::auth/pages/login.actions.register.before').' '.$this->registerAction->toHtml());
    }
}
