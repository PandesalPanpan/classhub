<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Schedule;
use Illuminate\Auth\Access\HandlesAuthorization;

class SchedulePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Schedule');
    }

    public function view(AuthUser $authUser, Schedule $schedule): bool
    {
        return $authUser->can('View:Schedule');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Schedule');
    }

    public function update(AuthUser $authUser, Schedule $schedule): bool
    {
        return $authUser->can('Update:Schedule');
    }

    public function delete(AuthUser $authUser, Schedule $schedule): bool
    {
        return $authUser->can('Delete:Schedule');
    }

}