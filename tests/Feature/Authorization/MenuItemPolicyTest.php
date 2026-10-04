<?php

use App\Modules\Menu\Models\MenuItem;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;

/*
|--------------------------------------------------------------------------
| Permission layer
|--------------------------------------------------------------------------
*/

test('admin can viewAny menu items', function () {
    expect(makeAdmin()->can('viewAny', MenuItem::class))->toBeTrue();
});

test('kitchen staff can viewAny menu items', function () {
    expect(makeKitchenStaff()->can('viewAny', MenuItem::class))->toBeTrue();
});

test('customer cannot viewAny menu items', function () {
    expect(makeCustomer()->can('viewAny', MenuItem::class))->toBeFalse();
});

test('admin can create menu items', function () {
    expect(makeAdmin()->can('create', MenuItem::class))->toBeTrue();
});

test('kitchen staff cannot create menu items', function () {
    expect(makeKitchenStaff()->can('create', MenuItem::class))->toBeFalse();
});

test('manager can update and delete menu items', function () {
    $manager = makeManager();
    $item = makeMenuItem();

    expect($manager->can('update', $item))->toBeTrue();
    expect($manager->can('delete', $item))->toBeTrue();
});

test('kitchen staff cannot update or delete menu items', function () {
    $kitchen = makeKitchenStaff();
    $item = makeMenuItem();

    expect($kitchen->can('update', $item))->toBeFalse();
    expect($kitchen->can('delete', $item))->toBeFalse();
});

test('only managers and admins can publish menus', function () {
    $admin = makeAdmin();
    $manager = makeManager();
    $kitchen = makeKitchenStaff();
    $customer = makeCustomer();

    expect($admin->can('publish', MenuItem::class))->toBeTrue();
    expect($manager->can('publish', MenuItem::class))->toBeTrue();
    expect($kitchen->can('publish', MenuItem::class))->toBeFalse();
    expect($customer->can('publish', MenuItem::class))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Business rule: canBeDeleted
|--------------------------------------------------------------------------
*/

test('a brand new item can be deleted', function () {
    $item = makeMenuItem();
    expect($item->canBeDeleted())->toBeTrue();
});

test('an item that appears in any order cannot be deleted', function () {
    $item = makeMenuItem();
    $customer = makeCustomer();
    $order = makeOrder($customer);

    OrderItem::create([
        'order_id'     => $order->id,
        'menu_item_id' => $item->id,
        'item_name'    => $item->name,
        'item_price'   => $item->price,
        'is_veg'       => $item->is_veg,
        'item_type'    => $item->type,
        'quantity'     => 1,
    ]);

    expect($item->fresh()->canBeDeleted())->toBeFalse();
});