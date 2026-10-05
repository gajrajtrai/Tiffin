<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class WalkInUserSeeder extends Seeder
{
    public function run(): void
    {
        $walkIn = User::firstOrCreate(
            ['mobile' => '00000000'],
            [
                'name'     => 'Walk-in / Phone',
                'email'    => null,
                'password' => Hash::make(bin2hex(random_bytes(16))),
                'status'   => 'active',
            ]
        );

        // No role assigned — this account is a placeholder for anonymous orders.
        // It must never be able to log in.

        $this->command->info('✓ Walk-in system user ready (mobile: 00000000).');
    }
}