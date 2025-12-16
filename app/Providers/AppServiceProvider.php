<?php

namespace App\Providers;

use App\Models\Room;
use App\Models\Schedule;
use App\Policies\RoomPolicy;
use App\Policies\SchedulePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        //
        Model::unguard();
        Gate::policy(Schedule::class, SchedulePolicy::class);
        Gate::policy(Room::class, RoomPolicy::class);

        // Model::preventLazyLoading();
    }
}
