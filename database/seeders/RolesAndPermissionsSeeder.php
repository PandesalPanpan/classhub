<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Use the same guard as your Filament / web users.
        $guard = config('auth.defaults.guard', 'web');

        // Ensure Superadmin role exists, using the name configured in filament-shield.php.
        $superAdminRoleName = config('filament-shield.super_admin.name', 'Superadmin');

        $superAdmin = Role::firstOrCreate(
            ['name' => $superAdminRoleName, 'guard_name' => $guard],
            []
        );

        // Main application roles
        $classRep = Role::firstOrCreate(
            ['name' => 'Class Representative', 'guard_name' => $guard],
            []
        );

        $admin = Role::firstOrCreate(
            ['name' => 'Admin', 'guard_name' => $guard],
            []
        );

        /*
         * Custom schedule workflow permissions.
         * These names align with the custom_permissions defined in config/filament-shield.php.
         */
        $schedulePermissions = collect([
            'Schedules:Approve',
            'Schedules:Reject',
            'Schedules:Cancel',
            'Schedules:BulkSchedule',
        ])->map(function (string $name) use ($guard) {
            return Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => $guard],
                []
            );
        });

        // Grant schedule workflow permissions to Admin and Superadmin.
        $admin->syncPermissions(
            $schedulePermissions->pluck('name')->toArray()
        );

        $superAdmin->givePermissionTo(
            $schedulePermissions->pluck('name')->toArray()
        );

        /*
         * Note:
         * - Class Representative is intentionally kept minimal here.
         *   You can assign resource/page/widget permissions to it from
         *   the Filament Shield "Roles" resource UI.
         *
         * - Superadmin will also be given all generated permissions by
         *   Shield when you run its artisan setup/permission generation
         *   commands, so the explicit grants above are just for clarity.
         */
    }
}


