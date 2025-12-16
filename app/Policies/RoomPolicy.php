<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;

class RoomPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:Room');
    }

    public function view(User $user, Room $room): bool
    {
        return $user->can('View:Room');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:Room');
    }

    public function update(User $user, Room $room): bool
    {
        return $user->can('Update:Room');
    }

    public function delete(User $user, Room $room): bool
    {
        return $user->can('Delete:Room');
    }

    public function restore(User $user, Room $room): bool
    {
        return $user->can('Restore:Room');
    }

    public function forceDelete(User $user, Room $room): bool
    {
        return $user->can('ForceDelete:Room');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:Room');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:Room');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:Room');
    }

    public function replicate(User $user, Room $room): bool
    {
        return $user->can('Replicate:Room');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:Room');
    }
}


