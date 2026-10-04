<?php

use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderService;
use App\Modules\Payment\Models\WalletTransaction;

beforeEach(function () {
    seedRolesAndPermissions();
    $this->admin = makeAdmin();
});

test('cancelling a pending order refunds the wallet', function () {
    $customer = makeCustomer(['wallet_balance' => 400]);
    $order = makeOrder($customer, ['total' => 100, 'status' => Order::STATUS_PENDING]);

    app(OrderService::class)->cancel($order, $this->admin, 'Customer changed mind');

    expect((float) $customer->fresh()->wallet_balance)->toBe(500.0);

    $refund = WalletTransaction::where('user_id', $customer->id)
        ->where('type', WalletTransaction::TYPE_REFUND)
        ->first();

    expect($refund)->not->toBeNull();
    expect((float) $refund->amount)->toBe(100.0);
});

test('cancelling from preparing also refunds', function () {
    $customer = makeCustomer(['wallet_balance' => 400]);
    $order = makeOrder($customer, ['total' => 100, 'status' => Order::STATUS_PREPARING]);

    app(OrderService::class)->cancel($order, $this->admin, 'Kitchen issue');

    expect($order->fresh()->status)->toBe(Order::STATUS_CANCELLED);
    expect((float) $customer->fresh()->wallet_balance)->toBe(500.0);
});

test('cancel sets cancelled_at and cancelled_reason', function () {
    $customer = makeCustomer(['wallet_balance' => 400]);
    $order = makeOrder($customer, ['total' => 100]);

    app(OrderService::class)->cancel($order, $this->admin, 'Test reason');

    $order->refresh();
    expect($order->cancelled_at)->not->toBeNull();
    expect($order->cancelled_reason)->toBe('Test reason');
});

test('cannot cancel a ready order', function () {
    $customer = makeCustomer(['wallet_balance' => 400]);
    $order = makeOrder($customer, ['total' => 100, 'status' => Order::STATUS_READY]);

    expect(fn () => app(OrderService::class)->cancel($order, $this->admin, 'Too late'))
        ->toThrow(RuntimeException::class);

    expect($order->fresh()->status)->toBe(Order::STATUS_READY);
});

test('cannot cancel a delivered order', function () {
    $customer = makeCustomer(['wallet_balance' => 400]);
    $order = makeOrder($customer, [
        'total'           => 100,
        'status'          => Order::STATUS_DELIVERED,
        'delivery_method' => Order::METHOD_DELIVERY,
    ]);

    expect(fn () => app(OrderService::class)->cancel($order, $this->admin, 'Too late'))
        ->toThrow(RuntimeException::class);
});

test('cannot cancel with empty reason', function () {
    $customer = makeCustomer(['wallet_balance' => 400]);
    $order = makeOrder($customer, ['total' => 100]);

    expect(fn () => app(OrderService::class)->cancel($order, $this->admin, '   '))
        ->toThrow(RuntimeException::class, 'A cancellation reason is required.');
});

test('cancelling a zero-total order does not create a refund entry', function () {
    $customer = makeCustomer(['wallet_balance' => 400]);
    $order = makeOrder($customer, ['total' => 0]);

    app(OrderService::class)->cancel($order, $this->admin, 'Test');

    expect(WalletTransaction::where('type', WalletTransaction::TYPE_REFUND)->count())->toBe(0);
    expect($order->fresh()->status)->toBe(Order::STATUS_CANCELLED);
});