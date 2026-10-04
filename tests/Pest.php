<?php

use App\Modules\Menu\Models\DailyMenu;
use App\Modules\Menu\Models\MenuItem;
use App\Modules\Menu\Models\ServiceDay;
use App\Modules\Order\Models\Order;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        // Every feature test needs roles/permissions available.
        // Idempotent seeder, safe to run repeatedly.
        $this->seed(RolesAndPermissionsSeeder::class);
    })
    ->in('Feature');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Create a user with the given role and wallet balance.
 *
 * wallet_balance is NOT fillable (deliberately excluded from mass-assignment
 * in User::$fillable so only WalletService can change it). We set it after
 * create() to give tests a head start with funds.
 */
function makeUserWithRole(string $role, string $mobilePrefix, array $attributes = []): User
{
    static $n = 0;
    $n++;

    // Pull wallet_balance out — we set it separately
    $wallet = $attributes['wallet_balance'] ?? 0;
    unset($attributes['wallet_balance']);

    $user = User::create(array_merge([
        'name'     => 'Test '.$role.' '.$n,
        'mobile'   => sprintf('%08d', $mobilePrefix * 1000000 + $n),
        'email'    => strtolower(str_replace(' ', '.', $role)).$n.'@test.local',
        'password' => Hash::make('password'),
        'status'   => 'active',
    ], $attributes));

    // Directly set wallet_balance (bypasses mass-assignment guard)
    if ($wallet > 0) {
        $user->wallet_balance = $wallet;
        $user->save();
    }

    $user->assignRole($role);

    return $user->fresh();
}

function makeCustomer(array $attributes = []): User
{
    return makeUserWithRole('Customer', 90, $attributes);
}

function makeAdmin(array $attributes = []): User
{
    return makeUserWithRole('Admin', 80, $attributes);
}

function makeManager(array $attributes = []): User
{
    return makeUserWithRole('Manager', 70, $attributes);
}

function makeKitchenStaff(array $attributes = []): User
{
    return makeUserWithRole('Kitchen Staff', 60, $attributes);
}

function seedRolesAndPermissions(): void
{
    // Now handled automatically by the global beforeEach
}

function makeMenuItem(array $attributes = []): MenuItem
{
    static $n = 0;
    $n++;

    return MenuItem::create(array_merge([
        'name'       => 'Test Item '.$n,
        'type'       => 'main',
        'is_veg'     => true,
        'price'      => 100,
        'sort_order' => 0,
        'is_active'  => true,
    ], $attributes));
}

function publishToday(MenuItem $item): DailyMenu
{
    return DailyMenu::firstOrCreate([
        'menu_item_id' => $item->id,
        'service_date' => today()->toDateString(),
    ]);
}

function openToday(): ServiceDay
{
    return ServiceDay::updateOrCreate(
        ['service_date' => today()->toDateString()],
        [
            'is_open'                => true,
            'delivery_cutoff_time'   => '23:59',
            'max_delivery_capacity'  => 500,
        ]
    );
}

function closeToday(): ServiceDay
{
    return ServiceDay::updateOrCreate(
        ['service_date' => today()->toDateString()],
        ['is_open' => false]
    );
}

function setTodayCutoffPassed(): ServiceDay
{
    return ServiceDay::updateOrCreate(
        ['service_date' => today()->toDateString()],
        [
            'is_open'              => true,
            'delivery_cutoff_time' => now()->subMinutes(5)->format('H:i'),
        ]
    );
}

function makeOrder(User $customer, array $attributes = []): Order
{
    return Order::create(array_merge([
        'user_id'         => $customer->id,
        'service_date'    => today(),
        'delivery_method' => Order::METHOD_PICKUP,
        'total'           => 100,
        'status'          => Order::STATUS_PENDING,
        'payment_status'  => 'paid',
    ], $attributes));
}