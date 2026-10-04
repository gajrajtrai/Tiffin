<?php

use App\Modules\Supplier\Models\PurchaseOrder;
use App\Modules\Supplier\Models\Supplier;

function makeSupplier(): Supplier
{
    static $n = 0;
    $n++;

    return Supplier::create([
        'name'      => 'Test Supplier '.$n,
        'is_active' => true,
    ]);
}

function makePo(array $attributes = []): PurchaseOrder
{
    return PurchaseOrder::create(array_merge([
        'supplier_id' => makeSupplier()->id,
        'order_date'  => today(),
        'status'      => PurchaseOrder::STATUS_DRAFT,
        'subtotal'    => 100,
        'tax'         => 0,
        'total'       => 100,
    ], $attributes));
}

/*
|--------------------------------------------------------------------------
| Permission layer
|--------------------------------------------------------------------------
*/

test('admin can viewAny purchase orders', function () {
    expect(makeAdmin()->can('viewAny', PurchaseOrder::class))->toBeTrue();
});

test('manager can viewAny purchase orders', function () {
    expect(makeManager()->can('viewAny', PurchaseOrder::class))->toBeTrue();
});

test('kitchen staff cannot viewAny purchase orders', function () {
    expect(makeKitchenStaff()->can('viewAny', PurchaseOrder::class))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Update — draft only
|--------------------------------------------------------------------------
*/

test('manager can update a draft PO', function () {
    $po = makePo(['status' => PurchaseOrder::STATUS_DRAFT]);
    expect(makeManager()->can('update', $po))->toBeTrue();
});

test('cannot update a sent PO', function () {
    $po = makePo(['status' => PurchaseOrder::STATUS_SENT]);
    expect(makeAdmin()->can('update', $po))->toBeFalse();
});

test('cannot update a received PO', function () {
    $po = makePo(['status' => PurchaseOrder::STATUS_RECEIVED]);
    expect(makeAdmin()->can('update', $po))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Send
|--------------------------------------------------------------------------
*/

test('manager can send a draft PO', function () {
    $po = makePo(['status' => PurchaseOrder::STATUS_DRAFT]);
    expect(makeManager()->can('send', $po))->toBeTrue();
});

test('cannot send an already-sent PO', function () {
    $po = makePo(['status' => PurchaseOrder::STATUS_SENT]);
    expect(makeAdmin()->can('send', $po))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Cancel
|--------------------------------------------------------------------------
*/

test('manager can cancel a draft PO', function () {
    $po = makePo(['status' => PurchaseOrder::STATUS_DRAFT]);
    expect(makeManager()->can('cancel', $po))->toBeTrue();
});

test('manager can cancel a sent PO', function () {
    $po = makePo(['status' => PurchaseOrder::STATUS_SENT]);
    expect(makeManager()->can('cancel', $po))->toBeTrue();
});

test('cannot cancel a partially-received PO', function () {
    $po = makePo(['status' => PurchaseOrder::STATUS_PARTIALLY_RECEIVED]);
    expect(makeAdmin()->can('cancel', $po))->toBeFalse();
});

test('cannot cancel a received PO', function () {
    $po = makePo(['status' => PurchaseOrder::STATUS_RECEIVED]);
    expect(makeAdmin()->can('cancel', $po))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Receive
|--------------------------------------------------------------------------
*/

test('manager can receive against a sent PO', function () {
    $po = makePo(['status' => PurchaseOrder::STATUS_SENT]);
    expect(makeManager()->can('receive', $po))->toBeTrue();
});

test('manager can receive against a partially-received PO', function () {
    $po = makePo(['status' => PurchaseOrder::STATUS_PARTIALLY_RECEIVED]);
    expect(makeManager()->can('receive', $po))->toBeTrue();
});

test('cannot receive against a draft PO', function () {
    $po = makePo(['status' => PurchaseOrder::STATUS_DRAFT]);
    expect(makeAdmin()->can('receive', $po))->toBeFalse();
});

test('kitchen staff can receive', function () {
    $po = makePo(['status' => PurchaseOrder::STATUS_SENT]);
    expect(makeKitchenStaff()->can('receive', $po))->toBeTrue();
});