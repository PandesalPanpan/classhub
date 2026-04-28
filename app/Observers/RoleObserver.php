<?php

namespace App\Observers;

use Spatie\Permission\Models\Role;

class RoleObserver
{
    public function created(Role $role): void
    {
        activity('role')
            ->performedOn($role)
            ->withProperties(['name' => $role->name, 'guard_name' => $role->guard_name])
            ->event('created')
            ->log("Role '{$role->name}' was created");
    }

    public function updated(Role $role): void
    {
        $dirty = $role->getDirty();
        if (empty($dirty)) {
            return;
        }

        activity('role')
            ->performedOn($role)
            ->withProperties([
                'old' => array_intersect_key($role->getOriginal(), $dirty),
                'attributes' => $dirty,
            ])
            ->event('updated')
            ->log("Role '{$role->name}' was updated");
    }

    public function deleted(Role $role): void
    {
        activity('role')
            ->performedOn($role)
            ->withProperties(['name' => $role->name])
            ->event('deleted')
            ->log("Role '{$role->name}' was deleted");
    }
}
