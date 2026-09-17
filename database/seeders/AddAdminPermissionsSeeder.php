<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AddAdminPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $newPermissions = [
            // User Management
            'user.view',
            'user.create',
            'user.edit',
            'user.delete',
            'user.assign-role',

            // Roles & Permissions
            'role.view',
            'role.create',
            'role.edit',
            'role.delete',
            'role.assign-permission',

            // Restaurant Settings
            'settings.view',
            'settings.edit',
        ];

        foreach ($newPermissions as $name) {
            Permission::firstOrCreate([
                'name'       => $name,
                'guard_name' => 'web',
            ]);
        }

        $this->command->info('✓ New permissions added: '.count($newPermissions));

        // Admin gets everything
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminRole->syncPermissions(Permission::all());
            $this->command->info('✓ Admin role updated with all '.Permission::count().' permissions');
        }

        // Manager gets read-only user/settings/role access
        $managerRole = Role::where('name', 'Manager')->first();
        if ($managerRole) {
            $managerRole->givePermissionTo(['user.view', 'settings.view', 'role.view']);
            $this->command->info('✓ Manager granted view-only access to users, roles, and settings');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->command->info('Done.');
    }
}