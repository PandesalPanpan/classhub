<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Room;
use Illuminate\Auth\Access\HandlesAuthorization;

class RoomPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Room');
    }

    public function view(AuthUser $authUser, Room $room): bool
    {
        return $authUser->can('View:Room');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Room');
    }

    public function update(AuthUser $authUser, Room $room): bool
    {
        return $authUser->can('Update:Room');
    }

    public function delete(AuthUser $authUser, Room $room): bool
    {
        return $authUser->can('Delete:Room');
    }

}