<?php

use App\Modules\Supplier\Models\GoodsReceipt;
use App\Modules\Supplier\Models\Supplier;

function makeGr(array $attributes = []): GoodsReceipt
{
    static $n = 0;
    $n++;

    $supplier = Supplier::create([
        'name'      => 'GR Supplier '.$n,
        'is_active' => true,
    ]);

    return GoodsReceipt::create(array_merge([
        'supplier_id'    => $supplier->id,
        'received_date'  => today(),
        'status'         => GoodsReceipt::STATUS_DRAFT,
        'payment_method' => 'cash',
        'subtotal'       => 100,
        'tax'            => 0,
        'total'          => 100,
    ], $attributes));
}

/*
|--------------------------------------------------------------------------
| View
|--------------------------------------------------------------------------
*/

test('admin can viewAny goods receipts', function () {
    expect(makeAdmin()->can('viewAny', GoodsReceipt::class))->toBeTrue();
});

test('kitchen staff can viewAny goods receipts', function () {
    expect(makeKitchenStaff()->can('viewAny', GoodsReceipt::class))->toBeTrue();
});

test('customer cannot viewAny goods receipts', function () {
    expect(makeCustomer()->can('viewAny', GoodsReceipt::class))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Update — draft only
|--------------------------------------------------------------------------
*/

test('manager can update a draft receipt', function () {
    $gr = makeGr(['status' => GoodsReceipt::STATUS_DRAFT]);
    expect(makeManager()->can('update', $gr))->toBeTrue();
});

test('cannot update a confirmed receipt', function () {
    $gr = makeGr(['status' => GoodsReceipt::STATUS_CONFIRMED]);
    expect(makeAdmin()->can('update', $gr))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Confirm — draft only
|--------------------------------------------------------------------------
*/

test('manager can confirm a draft receipt', function () {
    $gr = makeGr(['status' => GoodsReceipt::STATUS_DRAFT]);
    expect(makeManager()->can('confirm', $gr))->toBeTrue();
});

test('kitchen staff can confirm a draft receipt', function () {
    $gr = makeGr(['status' => GoodsReceipt::STATUS_DRAFT]);
    expect(makeKitchenStaff()->can('confirm', $gr))->toBeTrue();
});

test('cannot confirm a confirmed receipt', function () {
    $gr = makeGr(['status' => GoodsReceipt::STATUS_CONFIRMED]);
    expect(makeAdmin()->can('confirm', $gr))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Delete — draft only
|--------------------------------------------------------------------------
*/

test('manager can delete a draft receipt', function () {
    $gr = makeGr(['status' => GoodsReceipt::STATUS_DRAFT]);
    expect(makeManager()->can('delete', $gr))->toBeTrue();
});

test('cannot delete a confirmed receipt', function () {
    $gr = makeGr(['status' => GoodsReceipt::STATUS_CONFIRMED]);
    expect(makeAdmin()->can('delete', $gr))->toBeFalse();
});