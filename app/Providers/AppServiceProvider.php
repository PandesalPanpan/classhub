<?php

namespace App\Providers;

use App\Mail\User\VerifyEmailMail;
use App\Models\Room;
use App\Models\Schedule;
use App\Observers\RoleObserver;
use App\Observers\ScheduleObserver;
use App\Policies\RoomPolicy;
use App\Policies\SchedulePolicy;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->isProduction()) {
            URL::forceScheme('https');
        }

        Model::unguard();
        Schedule::observe(ScheduleObserver::class);
        Role::observe(RoleObserver::class);
        Gate::policy(Schedule::class, SchedulePolicy::class);
        Gate::policy(Room::class, RoomPolicy::class);
        // Model::preventLazyLoading();

        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return new VerifyEmailMail($notifiable, $url);
        });
    }
}
