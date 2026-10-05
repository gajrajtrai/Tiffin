<?php

use App\Modules\Menu\Models\DailyMenu;
use App\Modules\Menu\Models\MenuItem;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderPlacementService;

beforeEach(function () {
    openToday();
});

/*
|--------------------------------------------------------------------------
| Model-level: toggleSoldOut
|--------------------------------------------------------------------------
*/

test('item starts as not sold out after publish', function () {
    $item = makeMenuItem();
    publishToday($item);

    expect(DailyMenu::isSoldOut(today(), $item->id))->toBeFalse();
});

test('toggleSoldOut marks item as sold out', function () {
    $item = makeMenuItem();
    publishToday($item);

    $nowSoldOut = DailyMenu::toggleSoldOut(today(), $item->id);

    expect($nowSoldOut)->toBeTrue();
    expect(DailyMenu::isSoldOut(today(), $item->id))->toBeTrue();
});

test('toggling twice restocks the item', function () {
    $item = makeMenuItem();
    publishToday($item);

    DailyMenu::toggleSoldOut(today(), $item->id);
    $nowSoldOut = DailyMenu::toggleSoldOut(today(), $item->id);

    expect($nowSoldOut)->toBeFalse();
    expect(DailyMenu::isSoldOut(today(), $item->id))->toBeFalse();
});

test('toggling an unpublished item throws', function () {
    $item = makeMenuItem();

    expect(fn () => DailyMenu::toggleSoldOut(today(), $item->id))
        ->toThrow(RuntimeException::class);
});

test('publishedForDate annotates sold-out items', function () {
    $item = makeMenuItem(['type' => 'main']);
    publishToday($item);
    DailyMenu::toggleSoldOut(today(), $item->id);

    $grouped = DailyMenu::publishedForDate(today());
    $main = $grouped['main']->firstWhere('id', $item->id);

    expect($main)->not->toBeNull();
    expect($main->is_sold_out)->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Order placement rejection
|--------------------------------------------------------------------------
*/

test('a sold-out item cannot be ordered', function () {
    $customer = makeCustomer(['wallet_balance' => 1000]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);
    DailyMenu::toggleSoldOut(today(), $item->id);

    expect(fn () => app(OrderPlacementService::class)->place(
        customer: $customer,
        cart: [$item->id => 1],
        deliveryMethod: Order::METHOD_PICKUP,
    ))->toThrow(RuntimeException::class, 'sold out for today');
});

test('restocking an item lets it be ordered again', function () {
    $customer = makeCustomer(['wallet_balance' => 1000]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);
    DailyMenu::toggleSoldOut(today(), $item->id);
    DailyMenu::toggleSoldOut(today(), $item->id); // restock

    $order = app(OrderPlacementService::class)->place(
        customer: $customer,
        cart: [$item->id => 1],
        deliveryMethod: Order::METHOD_PICKUP,
    );

    expect($order->status)->toBe(Order::STATUS_PENDING);
});

test('existing orders for a sold-out item are unaffected', function () {
    $customer = makeCustomer(['wallet_balance' => 1000]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    // Place an order first
    $order = app(OrderPlacementService::class)->place(
        customer: $customer,
        cart: [$item->id => 1],
        deliveryMethod: Order::METHOD_PICKUP,
    );

    // Then mark it sold out
    DailyMenu::toggleSoldOut(today(), $item->id);

    // Order is still there, still active
    expect($order->fresh()->status)->toBe(Order::STATUS_PENDING);
});

/*
|--------------------------------------------------------------------------
| Sold-out is per-day
|--------------------------------------------------------------------------
*/

test('sold-out applies only to today, not tomorrow', function () {
    $item = makeMenuItem();
    publishToday($item);
    DailyMenu::toggleSoldOut(today(), $item->id);

    expect(DailyMenu::isSoldOut(today(), $item->id))->toBeTrue();
    expect(DailyMenu::isSoldOut(today()->addDay(), $item->id))->toBeFalse();
});