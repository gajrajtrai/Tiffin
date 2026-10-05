<?php

use App\Modules\Menu\Models\DailyMenu;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderPlacementService;
use App\Modules\Order\Services\OrderService;
use App\Modules\Payment\Models\WalletTransaction;

beforeEach(function () {
    openToday();
});

/*
|--------------------------------------------------------------------------
| Editability window
|--------------------------------------------------------------------------
*/

test('a fresh order is editable', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    $order = app(OrderPlacementService::class)
        ->place($customer, [$item->id => 1], Order::METHOD_PICKUP);

    expect($order->isEditable())->toBeTrue();
    expect($order->editable_until)->not->toBeNull();
});

test('an expired order is not editable', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    $order = app(OrderPlacementService::class)
        ->place($customer, [$item->id => 1], Order::METHOD_PICKUP);

    $order->editable_until = now()->subMinute();
    $order->save();

    expect($order->fresh()->isEditable())->toBeFalse();
});

test('a forwarded order is not editable even within window', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    $order = app(OrderPlacementService::class)
        ->place($customer, [$item->id => 1], Order::METHOD_PICKUP);

    $order->status = Order::STATUS_PREPARING;
    $order->save();

    expect($order->fresh()->isEditable())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Add item
|--------------------------------------------------------------------------
*/

test('customer can add an item within the edit window', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);
    $a = makeMenuItem(['price' => 100]);
    $b = makeMenuItem(['price' => 50]);
    publishToday($a);
    publishToday($b);

    $order = app(OrderPlacementService::class)
        ->place($customer, [$a->id => 1], Order::METHOD_PICKUP);

    $updated = app(OrderService::class)->addItem($order, $b->id, $customer);

    expect($updated->items)->toHaveCount(2);
    expect((float) $updated->total)->toBe(150.0);
    expect((float) $customer->fresh()->wallet_balance)->toBe(350.0);
});

test('adding the same item twice increments quantity', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    $order = app(OrderPlacementService::class)
        ->place($customer, [$item->id => 1], Order::METHOD_PICKUP);

    $updated = app(OrderService::class)->addItem($order, $item->id, $customer);

    expect($updated->items)->toHaveCount(1);
    expect((int) $updated->items->first()->quantity)->toBe(2);
    expect((float) $updated->total)->toBe(200.0);
});

test('cannot add item to an expired order', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    $order = app(OrderPlacementService::class)
        ->place($customer, [$item->id => 1], Order::METHOD_PICKUP);
    $order->editable_until = now()->subMinute();
    $order->save();

    expect(fn () => app(OrderService::class)->addItem($order->fresh(), $item->id, $customer))
        ->toThrow(RuntimeException::class, 'can no longer be edited');
});

test('cannot add a sold-out item to an order', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);
    $a = makeMenuItem(['price' => 100]);
    $b = makeMenuItem(['price' => 50]);
    publishToday($a);
    publishToday($b);
    DailyMenu::toggleSoldOut(today(), $b->id);

    $order = app(OrderPlacementService::class)
        ->place($customer, [$a->id => 1], Order::METHOD_PICKUP);

    expect(fn () => app(OrderService::class)->addItem($order, $b->id, $customer))
        ->toThrow(RuntimeException::class, 'sold out for today');
});

test('cannot add item if wallet balance is insufficient', function () {
    $customer = makeCustomer(['wallet_balance' => 120]);
    $a = makeMenuItem(['price' => 100]);
    $b = makeMenuItem(['price' => 50]);
    publishToday($a);
    publishToday($b);

    $order = app(OrderPlacementService::class)
        ->place($customer, [$a->id => 1], Order::METHOD_PICKUP);

    expect(fn () => app(OrderService::class)->addItem($order, $b->id, $customer))
        ->toThrow(RuntimeException::class, 'Insufficient wallet balance');
});

test('adding an item records a wallet debit', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);
    $a = makeMenuItem(['price' => 100]);
    $b = makeMenuItem(['price' => 50]);
    publishToday($a);
    publishToday($b);

    $order = app(OrderPlacementService::class)
        ->place($customer, [$a->id => 1], Order::METHOD_PICKUP);

    app(OrderService::class)->addItem($order, $b->id, $customer);

    $debits = WalletTransaction::where('user_id', $customer->id)
        ->where('type', WalletTransaction::TYPE_DEBIT)
        ->get();

    expect($debits)->toHaveCount(2);
    expect($debits->last()->description)->toContain('added');
});

/*
|--------------------------------------------------------------------------
| Remove item
|--------------------------------------------------------------------------
*/

test('customer can remove an item within the edit window', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);
    $a = makeMenuItem(['price' => 100]);
    $b = makeMenuItem(['price' => 50]);
    publishToday($a);
    publishToday($b);

    $order = app(OrderPlacementService::class)
        ->place($customer, [$a->id => 1, $b->id => 1], Order::METHOD_PICKUP);

    $itemToRemove = $order->items->firstWhere('menu_item_id', $b->id);
    $updated = app(OrderService::class)->removeItem($order, $itemToRemove->id, $customer);

    expect($updated->items)->toHaveCount(1);
    expect((float) $updated->total)->toBe(100.0);
    expect((float) $customer->fresh()->wallet_balance)->toBe(400.0);
});

test('cannot remove the last item in an order', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    $order = app(OrderPlacementService::class)
        ->place($customer, [$item->id => 1], Order::METHOD_PICKUP);

    $only = $order->items->first();

    expect(fn () => app(OrderService::class)->removeItem($order, $only->id, $customer))
        ->toThrow(RuntimeException::class, 'at least one item');
});

test('removing an item records a wallet refund', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);
    $a = makeMenuItem(['price' => 100]);
    $b = makeMenuItem(['price' => 50]);
    publishToday($a);
    publishToday($b);

    $order = app(OrderPlacementService::class)
        ->place($customer, [$a->id => 1, $b->id => 1], Order::METHOD_PICKUP);

    $itemToRemove = $order->items->firstWhere('menu_item_id', $b->id);
    app(OrderService::class)->removeItem($order, $itemToRemove->id, $customer);

    $refunds = WalletTransaction::where('user_id', $customer->id)
        ->where('type', WalletTransaction::TYPE_REFUND)
        ->get();

    expect($refunds)->toHaveCount(1);
    expect((float) $refunds->first()->amount)->toBe(50.0);
    expect($refunds->first()->description)->toContain('removed');
});

test('cannot remove item from an expired order', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);
    $a = makeMenuItem(['price' => 100]);
    $b = makeMenuItem(['price' => 50]);
    publishToday($a);
    publishToday($b);

    $order = app(OrderPlacementService::class)
        ->place($customer, [$a->id => 1, $b->id => 1], Order::METHOD_PICKUP);
    $order->editable_until = now()->subMinute();
    $order->save();

    $itemToRemove = $order->fresh()->items->firstWhere('menu_item_id', $b->id);

    expect(fn () => app(OrderService::class)->removeItem($order->fresh(), $itemToRemove->id, $customer))
        ->toThrow(RuntimeException::class, 'can no longer be edited');
});

/*
|--------------------------------------------------------------------------
| Change delivery method
|--------------------------------------------------------------------------
*/

test('customer can switch pickup to delivery before cutoff', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    $order = app(OrderPlacementService::class)
        ->place($customer, [$item->id => 1], Order::METHOD_PICKUP);

    $updated = app(OrderService::class)->changeDeliveryMethod($order, Order::METHOD_DELIVERY, $customer);

    expect($updated->delivery_method)->toBe(Order::METHOD_DELIVERY);
    expect($updated->delivery_slot)->not->toBeNull();
});

test('cannot switch to delivery after cutoff', function () {
    setTodayCutoffPassed();

    $customer = makeCustomer(['wallet_balance' => 500]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    $order = app(OrderPlacementService::class)
        ->place($customer, [$item->id => 1], Order::METHOD_PICKUP);

    expect(fn () => app(OrderService::class)->changeDeliveryMethod($order, Order::METHOD_DELIVERY, $customer))
        ->toThrow(RuntimeException::class, 'cut-off');
});

test('cannot change delivery method on an expired order', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    $order = app(OrderPlacementService::class)
        ->place($customer, [$item->id => 1], Order::METHOD_PICKUP);
    $order->editable_until = now()->subMinute();
    $order->save();

    expect(fn () => app(OrderService::class)->changeDeliveryMethod($order->fresh(), Order::METHOD_DELIVERY, $customer))
        ->toThrow(RuntimeException::class, 'can no longer be edited');
});

/*
|--------------------------------------------------------------------------
| Policy
|--------------------------------------------------------------------------
*/

test('customer can edit their own editable order', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    $order = app(OrderPlacementService::class)
        ->place($customer, [$item->id => 1], Order::METHOD_PICKUP);

    expect($customer->can('edit', $order))->toBeTrue();
});

test('customer cannot edit another customer order', function () {
    $c1 = makeCustomer(['wallet_balance' => 500]);
    $c2 = makeCustomer(['wallet_balance' => 500]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    $order = app(OrderPlacementService::class)
        ->place($c1, [$item->id => 1], Order::METHOD_PICKUP);

    expect($c2->can('edit', $order))->toBeFalse();
});