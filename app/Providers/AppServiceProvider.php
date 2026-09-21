<?php

namespace App\Providers;

use App\Modules\Core\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->overrideAppNameFromSettings();
    }

    /**
     * At request time, replace config('app.name') with the value from
     * the settings table. All existing views keep working unchanged.
     */
    protected function overrideAppNameFromSettings(): void
    {
        // Skip during console commands (migrations, seeders, tinker)
        if (app()->runningInConsole()) {
            return;
        }

        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            $name = Setting::get('restaurant_name');

            if ($name && is_string($name) && trim($name) !== '') {
                config(['app.name' => $name]);
            }
        } catch (\Throwable $e) {
            // Settings table may not be seeded yet on very first request.
            // Fall back to config default silently.
        }
    }
}