<?php

use App\Modules\Supplier\Models\GoodsReceipt;
use App\Modules\Supplier\Models\PurchaseOrder;
use App\Modules\Supplier\Models\Supplier;

function makeNumberSupplier(): Supplier
{
    static $n = 0;
    $n++;

    return Supplier::create([
        'name'      => 'Number Supplier '.$n,
        'is_active' => true,
    ]);
}

/*
|--------------------------------------------------------------------------
| Purchase Order number format
|--------------------------------------------------------------------------
*/

test('po_number uses PYYMMDD-NN format', function () {
    $po = PurchaseOrder::create([
        'supplier_id' => makeNumberSupplier()->id,
        'order_date'  => today(),
        'status'      => PurchaseOrder::STATUS_DRAFT,
        'subtotal'    => 100,
        'tax'         => 0,
        'total'       => 100,
    ]);

    expect($po->po_number)->toMatch('/^P\d{6}-\d{2}$/');
    expect(strlen($po->po_number))->toBe(10);
});

test('po sequence increments within the same day', function () {
    $supplier = makeNumberSupplier();

    $a = PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'order_date'  => today(),
        'status'      => PurchaseOrder::STATUS_DRAFT,
        'subtotal'    => 100, 'tax' => 0, 'total' => 100,
    ]);
    $b = PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'order_date'  => today(),
        'status'      => PurchaseOrder::STATUS_DRAFT,
        'subtotal'    => 100, 'tax' => 0, 'total' => 100,
    ]);

    expect(str_ends_with($a->po_number, '-01'))->toBeTrue();
    expect(str_ends_with($b->po_number, '-02'))->toBeTrue();
});

test('po sequence is per day, not global', function () {
    $supplier = makeNumberSupplier();

    $todayPo = PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'order_date'  => today(),
        'status'      => PurchaseOrder::STATUS_DRAFT,
        'subtotal'    => 100, 'tax' => 0, 'total' => 100,
    ]);
    $yesterdayPo = PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'order_date'  => today()->subDay(),
        'status'      => PurchaseOrder::STATUS_DRAFT,
        'subtotal'    => 100, 'tax' => 0, 'total' => 100,
    ]);

    expect(str_ends_with($todayPo->po_number, '-01'))->toBeTrue();
    expect(str_ends_with($yesterdayPo->po_number, '-01'))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Goods Receipt number format
|--------------------------------------------------------------------------
*/

test('receipt_number uses GYYMMDD-NN format', function () {
    $gr = GoodsReceipt::create([
        'supplier_id'    => makeNumberSupplier()->id,
        'received_date'  => today(),
        'status'         => GoodsReceipt::STATUS_DRAFT,
        'payment_method' => 'cash',
        'subtotal'       => 100,
        'tax'            => 0,
        'total'          => 100,
    ]);

    expect($gr->receipt_number)->toMatch('/^G\d{6}-\d{2}$/');
    expect(strlen($gr->receipt_number))->toBe(10);
});

test('gr sequence is per day', function () {
    $supplier = makeNumberSupplier();

    $todayGr = GoodsReceipt::create([
        'supplier_id' => $supplier->id,
        'received_date' => today(),
        'status' => GoodsReceipt::STATUS_DRAFT,
        'payment_method' => 'cash',
        'subtotal' => 100, 'tax' => 0, 'total' => 100,
    ]);
    $yesterdayGr = GoodsReceipt::create([
        'supplier_id' => $supplier->id,
        'received_date' => today()->subDay(),
        'status' => GoodsReceipt::STATUS_DRAFT,
        'payment_method' => 'cash',
        'subtotal' => 100, 'tax' => 0, 'total' => 100,
    ]);

    expect(str_ends_with($todayGr->receipt_number, '-01'))->toBeTrue();
    expect(str_ends_with($yesterdayGr->receipt_number, '-01'))->toBeTrue();
});