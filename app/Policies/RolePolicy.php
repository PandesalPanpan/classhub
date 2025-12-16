<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    protected function isSuperAdmin(User $user): bool
    {
        $superAdminName = config('filament-shield.super_admin.name', 'Superadmin');

        return $user->hasRole($superAdminName);
    }

    public function viewAny(User $user): bool
    {
        return $this->isSuperAdmin($user);
    }

    public function view(User $user, Role $role): bool
    {
        return $this->isSuperAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isSuperAdmin($user);
    }

    public function update(User $user, Role $role): bool
    {
        return $this->isSuperAdmin($user);
    }

    public function delete(User $user, Role $role): bool
    {
        return $this->isSuperAdmin($user);
    }

    public function restore(User $user, Role $role): bool
    {
        return $this->isSuperAdmin($user);
    }

    public function forceDelete(User $user, Role $role): bool
    {
        return $this->isSuperAdmin($user);
    }
}


