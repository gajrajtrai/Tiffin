<?php

use App\Models\User;
use App\Modules\Menu\Models\DailyMenu;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderPlacementService;

beforeEach(function () {
    openToday();
    $this->seed(\Database\Seeders\WalkInUserSeeder::class);
    $this->admin = makeAdmin();
});

/*
|--------------------------------------------------------------------------
| Walk-in (anonymous) orders
|--------------------------------------------------------------------------
*/

test('walk-in order uses the system user', function () {
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    $order = app(OrderPlacementService::class)->placeManual(
        customer: null,
        cart: [$item->id => 1],
        deliveryMethod: Order::METHOD_PICKUP,
        paymentMethod: 'cash',
        enteredBy: $this->admin,
    );

    $walkIn = User::where('mobile', '00000000')->first();
    expect($order->user_id)->toBe($walkIn->id);
    expect($order->isManual())->toBeTrue();
    expect($order->entered_by)->toBe($this->admin->id);
});

test('walk-in order is marked pending payment when paid by cash', function () {
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    $order = app(OrderPlacementService::class)->placeManual(
        customer: null,
        cart: [$item->id => 1],
        deliveryMethod: Order::METHOD_PICKUP,
        paymentMethod: 'cash',
        enteredBy: $this->admin,
    );

    expect($order->payment_status)->toBe('pending');
    expect($order->wallet_transaction_id)->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Named customer orders
|--------------------------------------------------------------------------
*/

test('manual order for a named customer debits wallet when paying by wallet', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    $order = app(OrderPlacementService::class)->placeManual(
        customer: $customer,
        cart: [$item->id => 2],
        deliveryMethod: Order::METHOD_PICKUP,
        paymentMethod: 'wallet',
        enteredBy: $this->admin,
    );

    expect((float) $customer->fresh()->wallet_balance)->toBe(300.0);
    expect($order->payment_status)->toBe('paid');
    expect($order->wallet_transaction_id)->not->toBeNull();
});

test('manual order for a named customer can pay by cash without wallet debit', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    $order = app(OrderPlacementService::class)->placeManual(
        customer: $customer,
        cart: [$item->id => 1],
        deliveryMethod: Order::METHOD_PICKUP,
        paymentMethod: 'cash',
        enteredBy: $this->admin,
    );

    expect((float) $customer->fresh()->wallet_balance)->toBe(500.0);
    expect($order->payment_status)->toBe('pending');
});

test('manual order with wallet payment rejects insufficient balance', function () {
    $customer = makeCustomer(['wallet_balance' => 50]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    expect(fn () => app(OrderPlacementService::class)->placeManual(
        customer: $customer,
        cart: [$item->id => 1],
        deliveryMethod: Order::METHOD_PICKUP,
        paymentMethod: 'wallet',
        enteredBy: $this->admin,
    ))->toThrow(RuntimeException::class, 'Insufficient wallet balance');
});

/*
|--------------------------------------------------------------------------
| Admin override — one-order-per-day rule is bypassed
|--------------------------------------------------------------------------
*/

test('admin can place a manual order for a customer who already has one', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    $service = app(OrderPlacementService::class);

    // First order through the customer app
    $service->place($customer, [$item->id => 1], Order::METHOD_PICKUP);

    // Second order entered manually — should succeed
    $manual = $service->placeManual(
        customer: $customer,
        cart: [$item->id => 1],
        deliveryMethod: Order::METHOD_PICKUP,
        paymentMethod: 'cash',
        enteredBy: $this->admin,
    );

    expect($manual->isManual())->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Daily limits still apply
|--------------------------------------------------------------------------
*/

test('manual order respects the daily item limit', function () {
    $item = makeMenuItem(['daily_limit' => 2, 'price' => 100]);
    publishToday($item);

    expect(fn () => app(OrderPlacementService::class)->placeManual(
        customer: null,
        cart: [$item->id => 3],
        deliveryMethod: Order::METHOD_PICKUP,
        paymentMethod: 'cash',
        enteredBy: $this->admin,
    ))->toThrow(RuntimeException::class, 'only 2 remaining');
});

test('manual order respects the sold-out flag', function () {
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);
    DailyMenu::toggleSoldOut(today(), $item->id);

    expect(fn () => app(OrderPlacementService::class)->placeManual(
        customer: null,
        cart: [$item->id => 1],
        deliveryMethod: Order::METHOD_PICKUP,
        paymentMethod: 'cash',
        enteredBy: $this->admin,
    ))->toThrow(RuntimeException::class, 'sold out for today');
});

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

test('empty cart throws', function () {
    expect(fn () => app(OrderPlacementService::class)->placeManual(
        customer: null,
        cart: [],
        deliveryMethod: Order::METHOD_PICKUP,
        paymentMethod: 'cash',
        enteredBy: $this->admin,
    ))->toThrow(RuntimeException::class, 'at least one item');
});

test('invalid payment method throws', function () {
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    expect(fn () => app(OrderPlacementService::class)->placeManual(
        customer: null,
        cart: [$item->id => 1],
        deliveryMethod: Order::METHOD_PICKUP,
        paymentMethod: 'cheque',
        enteredBy: $this->admin,
    ))->toThrow(RuntimeException::class, 'Invalid payment method');
});

test('wallet payment without a customer throws', function () {
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    expect(fn () => app(OrderPlacementService::class)->placeManual(
        customer: null,
        cart: [$item->id => 1],
        deliveryMethod: Order::METHOD_PICKUP,
        paymentMethod: 'wallet',
        enteredBy: $this->admin,
    ))->toThrow(RuntimeException::class, 'Wallet payment requires');
});