<?php

use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderService;

beforeEach(function () {
    seedRolesAndPermissions();
    $this->admin = makeAdmin();
});

test('pending to preparing is allowed', function () {
    $customer = makeCustomer();
    $order = makeOrder($customer, ['status' => Order::STATUS_PENDING]);

    $updated = app(OrderService::class)->transition($order, Order::STATUS_PREPARING, $this->admin);

    expect($updated->status)->toBe(Order::STATUS_PREPARING);
});

test('legacy confirmed to preparing is allowed', function () {
    $customer = makeCustomer();
    $order = makeOrder($customer, ['status' => Order::STATUS_CONFIRMED]);

    $updated = app(OrderService::class)->transition($order, Order::STATUS_PREPARING, $this->admin);

    expect($updated->status)->toBe(Order::STATUS_PREPARING);
});

test('preparing to ready is allowed', function () {
    $customer = makeCustomer();
    $order = makeOrder($customer, ['status' => Order::STATUS_PREPARING]);

    $updated = app(OrderService::class)->transition($order, Order::STATUS_READY, $this->admin);

    expect($updated->status)->toBe(Order::STATUS_READY);
});

test('ready to delivered is allowed for delivery orders', function () {
    $customer = makeCustomer();
    $order = makeOrder($customer, [
        'status'          => Order::STATUS_READY,
        'delivery_method' => Order::METHOD_DELIVERY,
    ]);

    $updated = app(OrderService::class)->transition($order, Order::STATUS_DELIVERED, $this->admin);

    expect($updated->status)->toBe(Order::STATUS_DELIVERED);
});

test('ready to picked_up is allowed for pickup orders', function () {
    $customer = makeCustomer();
    $order = makeOrder($customer, [
        'status'          => Order::STATUS_READY,
        'delivery_method' => Order::METHOD_PICKUP,
    ]);

    $updated = app(OrderService::class)->transition($order, Order::STATUS_PICKED_UP, $this->admin);

    expect($updated->status)->toBe(Order::STATUS_PICKED_UP);
});

test('cannot skip from pending to ready', function () {
    $customer = makeCustomer();
    $order = makeOrder($customer, ['status' => Order::STATUS_PENDING]);

    expect(fn () => app(OrderService::class)->transition($order, Order::STATUS_READY, $this->admin))
        ->toThrow(RuntimeException::class, "Cannot move order from 'pending' to 'ready'.");
});

test('cannot mark a pickup order as delivered', function () {
    $customer = makeCustomer();
    $order = makeOrder($customer, [
        'status'          => Order::STATUS_READY,
        'delivery_method' => Order::METHOD_PICKUP,
    ]);

    expect(fn () => app(OrderService::class)->transition($order, Order::STATUS_DELIVERED, $this->admin))
        ->toThrow(RuntimeException::class, 'Pickup orders should be marked as Picked Up');
});

test('cannot mark a delivery order as picked_up', function () {
    $customer = makeCustomer();
    $order = makeOrder($customer, [
        'status'          => Order::STATUS_READY,
        'delivery_method' => Order::METHOD_DELIVERY,
    ]);

    expect(fn () => app(OrderService::class)->transition($order, Order::STATUS_PICKED_UP, $this->admin))
        ->toThrow(RuntimeException::class, 'Delivery orders should be marked as Delivered');
});

test('cannot transition from cancelled', function () {
    $customer = makeCustomer();
    $order = makeOrder($customer, ['status' => Order::STATUS_CANCELLED]);

    expect(fn () => app(OrderService::class)->transition($order, Order::STATUS_PREPARING, $this->admin))
        ->toThrow(RuntimeException::class);
});