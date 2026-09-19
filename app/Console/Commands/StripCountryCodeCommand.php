<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class StripCountryCodeCommand extends Command
{
    protected $signature = 'phone:strip-country-code';
    protected $description = 'One-off: convert stored +975XXXXXXXX mobiles to 8-digit local format';

    public function handle(): int
    {
        $count = 0;

        User::query()
            ->whereNotNull('mobile')
            ->where('mobile', 'like', '+975%')
            ->chunkById(100, function ($users) use (&$count) {
                foreach ($users as $user) {
                    $local = preg_replace('/^\+975/', '', (string) $user->mobile);

                    if (preg_match('/^\d{8}$/', $local)) {
                        $user->mobile = $local;
                        $user->save();
                        $count++;
                    }
                }
            });

        $this->info("Converted {$count} mobile numbers to local format.");
        return self::SUCCESS;
    }
}