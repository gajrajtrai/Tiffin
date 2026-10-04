<?php

namespace App\Providers;

use App\Modules\Core\Models\Setting;
use App\Modules\Order\Models\Order;
use App\Policies\OrderPolicy;
use Illuminate\Support\Facades\Gate;
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
        $this->registerPolicies();
        $this->overrideAppNameFromSettings();
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

    protected function registerPolicies(): void
    {
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(\App\Modules\Menu\Models\MenuItem::class, \App\Policies\MenuItemPolicy::class);
        Gate::policy(\App\Modules\Payment\Models\PaymentProof::class, \App\Policies\PaymentProofPolicy::class);
        Gate::policy(\App\Modules\Expense\Models\Expense::class, \App\Policies\ExpensePolicy::class);
        Gate::policy(\App\Modules\Inventory\Models\InventoryItem::class, \App\Policies\InventoryItemPolicy::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Settings-driven app name
    |--------------------------------------------------------------------------
    |
    | Replaces config('app.name') with the value from the settings table,
    | so every view that references config('app.name') reflects the current
    | restaurant name.
    |
    */

    protected function overrideAppNameFromSettings(): void
    {
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