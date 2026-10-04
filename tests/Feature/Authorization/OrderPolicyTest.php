<?php

use App\Modules\Order\Models\Order;

/*
|--------------------------------------------------------------------------
| OrderPolicy — permission layer
|--------------------------------------------------------------------------
*/

test('admin can view any order via viewAny', function () {
    $admin = makeAdmin();
    expect($admin->can('viewAny', Order::class))->toBeTrue();
});

test('kitchen staff can view any order via viewAny', function () {
    $kitchen = makeKitchenStaff();
    expect($kitchen->can('viewAny', Order::class))->toBeTrue();
});

test('customer cannot viewAny', function () {
    $customer = makeCustomer();
    expect($customer->can('viewAny', Order::class))->toBeFalse();
});

test('customer can view their own order', function () {
    $customer = makeCustomer();
    $order = makeOrder($customer);

    expect($customer->can('view', $order))->toBeTrue();
});

test('customer cannot view another customer order', function () {
    $customer1 = makeCustomer();
    $customer2 = makeCustomer();
    $order = makeOrder($customer1);

    expect($customer2->can('view', $order))->toBeFalse();
});

test('staff can view any customer order', function () {
    $customer = makeCustomer();
    $order = makeOrder($customer);
    $admin = makeAdmin();
    $kitchen = makeKitchenStaff();

    expect($admin->can('view', $order))->toBeTrue();
    expect($kitchen->can('view', $order))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| OrderPolicy — update state rules
|--------------------------------------------------------------------------
*/

test('update allowed on active orders for staff with permission', function () {
    $admin = makeAdmin();
    $order = makeOrder(makeCustomer(), ['status' => Order::STATUS_PENDING]);

    expect($admin->can('update', $order))->toBeTrue();
});

test('update denied on cancelled orders', function () {
    $admin = makeAdmin();
    $order = makeOrder(makeCustomer(), ['status' => Order::STATUS_CANCELLED]);

    expect($admin->can('update', $order))->toBeFalse();
});

test('update denied on delivered orders', function () {
    $admin = makeAdmin();
    $order = makeOrder(makeCustomer(), [
        'status' => Order::STATUS_DELIVERED,
        'delivery_method' => Order::METHOD_DELIVERY,
    ]);

    expect($admin->can('update', $order))->toBeFalse();
});

test('customer cannot update any order', function () {
    $customer = makeCustomer();
    $order = makeOrder($customer, ['status' => Order::STATUS_PENDING]);

    expect($customer->can('update', $order))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| OrderPolicy — forward state rules
|--------------------------------------------------------------------------
*/

test('forward allowed from pending', function () {
    $admin = makeAdmin();
    $order = makeOrder(makeCustomer(), ['status' => Order::STATUS_PENDING]);

    expect($admin->can('forward', $order))->toBeTrue();
});

test('forward allowed from legacy confirmed', function () {
    $admin = makeAdmin();
    $order = makeOrder(makeCustomer(), ['status' => Order::STATUS_CONFIRMED]);

    expect($admin->can('forward', $order))->toBeTrue();
});

test('forward denied from preparing', function () {
    $admin = makeAdmin();
    $order = makeOrder(makeCustomer(), ['status' => Order::STATUS_PREPARING]);

    expect($admin->can('forward', $order))->toBeFalse();
});

test('kitchen staff can forward', function () {
    $kitchen = makeKitchenStaff();
    $order = makeOrder(makeCustomer(), ['status' => Order::STATUS_PENDING]);

    expect($kitchen->can('forward', $order))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| OrderPolicy — advance state rules
|--------------------------------------------------------------------------
*/

test('advance allowed from preparing', function () {
    $admin = makeAdmin();
    $order = makeOrder(makeCustomer(), ['status' => Order::STATUS_PREPARING]);

    expect($admin->can('advance', $order))->toBeTrue();
});

test('advance allowed from ready', function () {
    $admin = makeAdmin();
    $order = makeOrder(makeCustomer(), [
        'status' => Order::STATUS_READY,
        'delivery_method' => Order::METHOD_DELIVERY,
    ]);

    expect($admin->can('advance', $order))->toBeTrue();
});

test('advance denied from pending', function () {
    $admin = makeAdmin();
    $order = makeOrder(makeCustomer(), ['status' => Order::STATUS_PENDING]);

    expect($admin->can('advance', $order))->toBeFalse();
});

test('advance denied from cancelled', function () {
    $admin = makeAdmin();
    $order = makeOrder(makeCustomer(), ['status' => Order::STATUS_CANCELLED]);

    expect($admin->can('advance', $order))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| OrderPolicy — cancel
|--------------------------------------------------------------------------
*/

test('manager can cancel a pending order', function () {
    $manager = makeManager();
    $order = makeOrder(makeCustomer(), ['status' => Order::STATUS_PENDING]);

    expect($manager->can('cancel', $order))->toBeTrue();
});

test('kitchen staff cannot cancel', function () {
    $kitchen = makeKitchenStaff();
    $order = makeOrder(makeCustomer(), ['status' => Order::STATUS_PENDING]);

    expect($kitchen->can('cancel', $order))->toBeFalse();
});

test('customer cannot cancel their own order via policy', function () {
    $customer = makeCustomer();
    $order = makeOrder($customer, ['status' => Order::STATUS_PENDING]);

    expect($customer->can('cancel', $order))->toBeFalse();
});

test('cancel denied from ready', function () {
    $admin = makeAdmin();
    $order = makeOrder(makeCustomer(), ['status' => Order::STATUS_READY]);

    expect($admin->can('cancel', $order))->toBeFalse();
});

test('cancel denied from delivered', function () {
    $admin = makeAdmin();
    $order = makeOrder(makeCustomer(), [
        'status' => Order::STATUS_DELIVERED,
        'delivery_method' => Order::METHOD_DELIVERY,
    ]);

    expect($admin->can('cancel', $order))->toBeFalse();
});