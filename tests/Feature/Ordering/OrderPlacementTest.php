<?php

use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Services\OrderPlacementService;
use App\Modules\Payment\Models\WalletTransaction;

beforeEach(function () {
    seedRolesAndPermissions();
    openToday();
});

test('a customer with sufficient balance can place an order', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);
    $item = makeMenuItem(['price' => 120]);
    publishToday($item);

    $order = app(OrderPlacementService::class)->place(
        customer: $customer,
        cart: [$item->id => 1],
        deliveryMethod: Order::METHOD_PICKUP,
    );

    expect($order)->toBeInstanceOf(Order::class);
    expect($order->status)->toBe(Order::STATUS_PENDING);
    expect($order->user_id)->toBe($customer->id);
    expect((float) $order->total)->toBe(120.0);
    expect($order->order_number)->toStartWith('TTF-');
});

test('order total is sum of price times quantity', function () {
    $customer = makeCustomer(['wallet_balance' => 1000]);
    $a = makeMenuItem(['price' => 100]);
    $b = makeMenuItem(['price' => 50]);
    publishToday($a);
    publishToday($b);

    $order = app(OrderPlacementService::class)->place(
        customer: $customer,
        cart: [$a->id => 2, $b->id => 3], // 200 + 150 = 350
        deliveryMethod: Order::METHOD_PICKUP,
    );

    expect((float) $order->total)->toBe(350.0);
});

test('order items are created with correct quantities', function () {
    $customer = makeCustomer(['wallet_balance' => 1000]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    $order = app(OrderPlacementService::class)->place(
        customer: $customer,
        cart: [$item->id => 3],
        deliveryMethod: Order::METHOD_PICKUP,
    );

    $orderItem = OrderItem::where('order_id', $order->id)->first();
    expect($orderItem->quantity)->toBe(3);
    expect((float) $orderItem->item_price)->toBe(100.0);
});

test('placing an order debits the wallet by the order total', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);
    $item = makeMenuItem(['price' => 120]);
    publishToday($item);

    app(OrderPlacementService::class)->place(
        customer: $customer,
        cart: [$item->id => 1],
        deliveryMethod: Order::METHOD_PICKUP,
    );

    expect((float) $customer->fresh()->wallet_balance)->toBe(380.0);

    $txn = WalletTransaction::where('user_id', $customer->id)
        ->where('type', WalletTransaction::TYPE_DEBIT)
        ->first();

    expect($txn)->not->toBeNull();
    expect((float) $txn->amount)->toBe(120.0);
});

test('order carries wallet_transaction_id after placement', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    $order = app(OrderPlacementService::class)->place(
        customer: $customer,
        cart: [$item->id => 1],
        deliveryMethod: Order::METHOD_PICKUP,
    );

    expect($order->wallet_transaction_id)->not->toBeNull();
});

test('insufficient balance throws and leaves wallet untouched', function () {
    $customer = makeCustomer(['wallet_balance' => 50]);
    $item = makeMenuItem(['price' => 120]);
    publishToday($item);

    expect(fn () => app(OrderPlacementService::class)->place(
        customer: $customer,
        cart: [$item->id => 1],
        deliveryMethod: Order::METHOD_PICKUP,
    ))->toThrow(RuntimeException::class, 'Insufficient wallet balance.');

    expect((float) $customer->fresh()->wallet_balance)->toBe(50.0);
    expect(Order::count())->toBe(0);
    expect(WalletTransaction::count())->toBe(0);
});

test('non-customer cannot place an order', function () {
    $admin = makeAdmin();
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    expect(fn () => app(OrderPlacementService::class)->place(
        customer: $admin,
        cart: [$item->id => 1],
        deliveryMethod: Order::METHOD_PICKUP,
    ))->toThrow(RuntimeException::class, 'Only customers can place orders.');
});

test('empty cart throws', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);

    expect(fn () => app(OrderPlacementService::class)->place(
        customer: $customer,
        cart: [],
        deliveryMethod: Order::METHOD_PICKUP,
    ))->toThrow(RuntimeException::class, 'Please select at least one item.');
});

test('cart with only zero quantities throws', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    expect(fn () => app(OrderPlacementService::class)->place(
        customer: $customer,
        cart: [$item->id => 0],
        deliveryMethod: Order::METHOD_PICKUP,
    ))->toThrow(RuntimeException::class, 'Please select at least one item.');
});

test('invalid delivery method throws', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    expect(fn () => app(OrderPlacementService::class)->place(
        customer: $customer,
        cart: [$item->id => 1],
        deliveryMethod: 'teleport',
    ))->toThrow(RuntimeException::class, 'Invalid delivery method.');
});

test('closed service day prevents ordering', function () {
    closeToday();
    $customer = makeCustomer(['wallet_balance' => 500]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    expect(fn () => app(OrderPlacementService::class)->place(
        customer: $customer,
        cart: [$item->id => 1],
        deliveryMethod: Order::METHOD_PICKUP,
    ))->toThrow(RuntimeException::class, 'We are closed today.');
});

test('delivery after cutoff is rejected', function () {
    setTodayCutoffPassed();
    $customer = makeCustomer(['wallet_balance' => 500]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    expect(fn () => app(OrderPlacementService::class)->place(
        customer: $customer,
        cart: [$item->id => 1],
        deliveryMethod: Order::METHOD_DELIVERY,
    ))->toThrow(RuntimeException::class, 'Delivery cut-off was at');
});

test('pickup still allowed after delivery cutoff', function () {
    setTodayCutoffPassed();
    $customer = makeCustomer(['wallet_balance' => 500]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    $order = app(OrderPlacementService::class)->place(
        customer: $customer,
        cart: [$item->id => 1],
        deliveryMethod: Order::METHOD_PICKUP,
    );

    expect($order->delivery_method)->toBe(Order::METHOD_PICKUP);
});

test('inactive menu item cannot be ordered', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);
    $item = makeMenuItem(['price' => 100, 'is_active' => false]);
    publishToday($item);

    expect(fn () => app(OrderPlacementService::class)->place(
        customer: $customer,
        cart: [$item->id => 1],
        deliveryMethod: Order::METHOD_PICKUP,
    ))->toThrow(RuntimeException::class, 'One or more items are no longer available.');
});

test('item not published today cannot be ordered', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);
    $item = makeMenuItem(['price' => 100]);
    // Deliberately not published

    expect(fn () => app(OrderPlacementService::class)->place(
        customer: $customer,
        cart: [$item->id => 1],
        deliveryMethod: Order::METHOD_PICKUP,
    ))->toThrow(RuntimeException::class, 'is not available today');
});

test('a customer cannot have two active orders for the same day', function () {
    $customer = makeCustomer(['wallet_balance' => 1000]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    $service = app(OrderPlacementService::class);

    $service->place($customer, [$item->id => 1], Order::METHOD_PICKUP);

    expect(fn () => $service->place($customer, [$item->id => 1], Order::METHOD_PICKUP))
        ->toThrow(RuntimeException::class, 'You already have an active order for today.');
});

test('a customer can place a new order after the previous one was delivered', function () {
    $customer = makeCustomer(['wallet_balance' => 1000]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    $first = app(OrderPlacementService::class)->place($customer, [$item->id => 1], Order::METHOD_PICKUP);
    $first->status = Order::STATUS_DELIVERED;
    $first->save();

    $second = app(OrderPlacementService::class)->place($customer, [$item->id => 1], Order::METHOD_PICKUP);

    expect($second->id)->not->toBe($first->id);
});

test('delivery order gets a delivery slot', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    $order = app(OrderPlacementService::class)->place(
        customer: $customer,
        cart: [$item->id => 1],
        deliveryMethod: Order::METHOD_DELIVERY,
    );

    expect($order->delivery_slot)->not->toBeNull();
    expect($order->delivery_method)->toBe(Order::METHOD_DELIVERY);
});

test('pickup order has no delivery slot', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    $order = app(OrderPlacementService::class)->place(
        customer: $customer,
        cart: [$item->id => 1],
        deliveryMethod: Order::METHOD_PICKUP,
    );

    expect($order->delivery_slot)->toBeNull();
});