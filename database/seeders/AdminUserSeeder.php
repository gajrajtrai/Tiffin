<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@tamkulay.test'],
            [
                'name'     => 'Tamkulay Admin',
                'password' => Hash::make('ChangeMe!2026'),
            ]
        );

        $admin->assignRole('Admin');

        $this->command->info('✓ Admin user created: admin@tamkulay.test / ChangeMe!2026');
        $this->command->warn('  → Change this password immediately after first login.');
    }
}