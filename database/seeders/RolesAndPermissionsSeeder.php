<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $config = config('tiffin');

        if (! $config || empty($config['roles'])) {
            $this->command->error('config/tiffin.php is missing or empty. Aborting.');
            return;
        }

        // 1. Collect every permission from every role
        $allPermissions = [];
        foreach ($config['roles'] as $roleName => $roleData) {
            foreach ($roleData['permissions'] as $perm) {
                if ($perm !== '*') {
                    $allPermissions[$perm] = true;
                }
            }
        }

        // 2. Create all permissions
        foreach (array_keys($allPermissions) as $permName) {
            Permission::firstOrCreate([
                'name'       => $permName,
                'guard_name' => 'web',
            ]);
        }
        $this->command->info('✓ Permissions created: '.count($allPermissions));

        // 3. Create roles and assign permissions
        foreach ($config['roles'] as $roleName => $roleData) {
            $role = Role::firstOrCreate([
                'name'       => $roleName,
                'guard_name' => 'web',
            ]);

            if (in_array('*', $roleData['permissions'], true)) {
                // Admin gets all permissions
                $role->syncPermissions(Permission::all());
                $this->command->info("✓ Role [{$roleName}] → ALL permissions");
            } else {
                $role->syncPermissions($roleData['permissions']);
                $this->command->info("✓ Role [{$roleName}] → ".count($roleData['permissions']).' permissions');
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->command->info('Permissions matrix seeded successfully.');
    }
}