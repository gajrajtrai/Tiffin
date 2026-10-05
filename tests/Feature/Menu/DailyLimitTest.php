<?php

use App\Modules\Menu\Models\DailyMenu;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderPlacementService;
use App\Modules\Order\Services\OrderService;

beforeEach(function () {
    openToday();
});

test('an item with no daily_limit has remainingToday null', function () {
    $item = makeMenuItem(['daily_limit' => null]);
    expect($item->remainingToday())->toBeNull();
    expect($item->isLimitReached())->toBeFalse();
});

test('an item with daily_limit reports remaining correctly', function () {
    $item = makeMenuItem(['daily_limit' => 10]);
    expect($item->remainingToday())->toBe(10);
    expect($item->isLimitReached())->toBeFalse();
});

test('orderedToday counts quantities from active orders', function () {
    $item = makeMenuItem(['daily_limit' => 10, 'price' => 100]);
    publishToday($item);

    $c1 = makeCustomer(['wallet_balance' => 500]);
    $c2 = makeCustomer(['wallet_balance' => 500]);

    $service = app(OrderPlacementService::class);
    $service->place($c1, [$item->id => 3], Order::METHOD_PICKUP);
    $service->place($c2, [$item->id => 2], Order::METHOD_PICKUP);

    expect($item->fresh()->orderedToday())->toBe(5);
    expect($item->fresh()->remainingToday())->toBe(5);
});

test('cancelled orders do not count toward the limit', function () {
    $item = makeMenuItem(['daily_limit' => 10, 'price' => 100]);
    publishToday($item);

    $customer = makeCustomer(['wallet_balance' => 500]);
    $service = app(OrderPlacementService::class);

    $order = $service->place($customer, [$item->id => 5], Order::METHOD_PICKUP);
    expect($item->fresh()->orderedToday())->toBe(5);

    $admin = makeAdmin();
    app(OrderService::class)->cancel($order, $admin, 'Test');

    expect($item->fresh()->orderedToday())->toBe(0);
    expect($item->fresh()->remainingToday())->toBe(10);
});

test('placing an order that exceeds the daily limit is rejected', function () {
    $item = makeMenuItem(['daily_limit' => 3, 'price' => 100]);
    publishToday($item);

    $customer = makeCustomer(['wallet_balance' => 500]);

    expect(fn () => app(OrderPlacementService::class)
        ->place($customer, [$item->id => 4], Order::METHOD_PICKUP))
        ->toThrow(RuntimeException::class, "only 3 remaining");
});

test('placing an order exactly at the daily limit succeeds', function () {
    $item = makeMenuItem(['daily_limit' => 3, 'price' => 100]);
    publishToday($item);

    $customer = makeCustomer(['wallet_balance' => 500]);

    $order = app(OrderPlacementService::class)
        ->place($customer, [$item->id => 3], Order::METHOD_PICKUP);

    expect($order->status)->toBe(Order::STATUS_PENDING);
    expect($item->fresh()->remainingToday())->toBe(0);
    expect($item->fresh()->isLimitReached())->toBeTrue();
});

test('adding an item via edit checks the daily limit', function () {
    $item = makeMenuItem(['daily_limit' => 2, 'price' => 100]);
    $other = makeMenuItem(['price' => 50]);
    publishToday($item);
    publishToday($other);

    $customer = makeCustomer(['wallet_balance' => 500]);
    $order = app(OrderPlacementService::class)
        ->place($customer, [$other->id => 1], Order::METHOD_PICKUP);

    // Add 2 of the limited item — should succeed
    app(OrderService::class)->addItem($order, $item->id, $customer);
    app(OrderService::class)->addItem($order->fresh(), $item->id, $customer);

    // Third attempt should fail
    expect(fn () => app(OrderService::class)->addItem($order->fresh(), $item->id, $customer))
        ->toThrow(RuntimeException::class, "reached today's limit");
});

test('sold out flag still takes precedence over limit', function () {
    $item = makeMenuItem(['daily_limit' => 10, 'price' => 100]);
    publishToday($item);
    DailyMenu::toggleSoldOut(today(), $item->id);

    $customer = makeCustomer(['wallet_balance' => 500]);

    expect(fn () => app(OrderPlacementService::class)
        ->place($customer, [$item->id => 1], Order::METHOD_PICKUP))
        ->toThrow(RuntimeException::class, 'sold out for today');
});

test('remaining_today is annotated on publishedForDate', function () {
    $item = makeMenuItem(['daily_limit' => 5, 'type' => 'main', 'price' => 100]);
    publishToday($item);

    $customer = makeCustomer(['wallet_balance' => 500]);
    app(OrderPlacementService::class)
        ->place($customer, [$item->id => 2], Order::METHOD_PICKUP);

    $grouped = DailyMenu::publishedForDate(today());
    $annotated = $grouped['main']->firstWhere('id', $item->id);

    expect($annotated->remaining_today)->toBe(3);
    expect($annotated->is_limit_reached)->toBeFalse();
});