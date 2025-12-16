<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;

class SchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Schedule');
    }

    public function view(User $user, Schedule $schedule): bool
    {
        return $user->can('View:Schedule');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Schedule');
    }

    public function update(User $user, Schedule $schedule): bool
    {
        return $user->can('Update:Schedule');
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        return $user->can('Delete:Schedule');
    }

    public function restore(User $user, Schedule $schedule): bool
    {
        return $user->can('Restore:Schedule');
    }

    public function forceDelete(User $user, Schedule $schedule): bool
    {
        return $user->can('ForceDelete:Schedule');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:Schedule');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:Schedule');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:Schedule');
    }

    public function replicate(User $user, Schedule $schedule): bool
    {
        return $user->can('Replicate:Schedule');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:Schedule');
    }
}


