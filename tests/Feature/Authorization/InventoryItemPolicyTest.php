<?php

use App\Modules\Inventory\Models\InventoryItem;

function makeItem(array $attributes = []): InventoryItem
{
    static $n = 0;
    $n++;

    return InventoryItem::create(array_merge([
        'name'          => 'Item '.$n,
        'category'      => 'dry_goods',
        'unit'          => 'kg',
        'current_stock' => 0,
        'reorder_level' => 0,
        'unit_cost'     => 100,
        'is_active'     => true,
    ], $attributes));
}

/*
|--------------------------------------------------------------------------
| View
|--------------------------------------------------------------------------
*/

test('admin can viewAny inventory', function () {
    expect(makeAdmin()->can('viewAny', InventoryItem::class))->toBeTrue();
});

test('manager can viewAny inventory', function () {
    expect(makeManager()->can('viewAny', InventoryItem::class))->toBeTrue();
});

test('kitchen staff can viewAny inventory', function () {
    expect(makeKitchenStaff()->can('viewAny', InventoryItem::class))->toBeTrue();
});

test('customer cannot viewAny inventory', function () {
    expect(makeCustomer()->can('viewAny', InventoryItem::class))->toBeFalse();
});

test('kitchen staff can view a specific item', function () {
    $item = makeItem();
    expect(makeKitchenStaff()->can('view', $item))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Create / Update
|--------------------------------------------------------------------------
*/

test('manager can create inventory items', function () {
    expect(makeManager()->can('create', InventoryItem::class))->toBeTrue();
});

test('kitchen staff cannot create inventory items', function () {
    expect(makeKitchenStaff()->can('create', InventoryItem::class))->toBeFalse();
});

test('manager can update an item', function () {
    $item = makeItem();
    expect(makeManager()->can('update', $item))->toBeTrue();
});

test('kitchen staff cannot update an item', function () {
    $item = makeItem();
    expect(makeKitchenStaff()->can('update', $item))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Adjust
|--------------------------------------------------------------------------
*/

test('manager can adjust stock on an item', function () {
    $item = makeItem();
    expect(makeManager()->can('adjust', $item))->toBeTrue();
});

test('kitchen staff cannot adjust stock on an item', function () {
    $item = makeItem();
    expect(makeKitchenStaff()->can('adjust', $item))->toBeFalse();
});

test('customer cannot adjust stock', function () {
    $item = makeItem();
    expect(makeCustomer()->can('adjust', $item))->toBeFalse();
});