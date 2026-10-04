<?php

use App\Modules\Expense\Models\Expense;
use App\Modules\Expense\Models\ExpenseCategory;
use App\Modules\Supplier\Models\GoodsReceipt;
use App\Modules\Supplier\Models\Supplier;
function makeCategory(): ExpenseCategory
{
    static $n = 0;
    $n++;

    return ExpenseCategory::create([
        'name'       => 'Test Cat '.$n,
        'slug'       => 'test-cat-'.$n,
        'color'      => 'slate',
        'sort_order' => 0,
        'is_active'  => true,
    ]);
}

function makeExpense(array $attributes = []): Expense
{
    return Expense::create(array_merge([
        'expense_category_id' => makeCategory()->id,
        'expense_date'        => today(),
        'description'         => 'Test expense',
        'amount'              => 100,
        'payment_method'      => Expense::METHOD_CASH,
        'status'              => Expense::STATUS_PAID,
    ], $attributes));
}
function makeGoodsReceipt(): GoodsReceipt
{
    static $n = 0;
    $n++;

    $supplier = Supplier::create([
        'name'      => 'Test Supplier '.$n,
        'is_active' => true,
    ]);

    return GoodsReceipt::create([
        'supplier_id'   => $supplier->id,
        'received_date' => today(),
        'status'        => GoodsReceipt::STATUS_CONFIRMED,
        'payment_method'=> 'cash',
        'subtotal'      => 100,
        'tax'           => 0,
        'total'         => 100,
    ]);
}
/*
|--------------------------------------------------------------------------
| Permissions
|--------------------------------------------------------------------------
*/

test('admin can viewAny expenses', function () {
    expect(makeAdmin()->can('viewAny', Expense::class))->toBeTrue();
});

test('manager can viewAny expenses', function () {
    expect(makeManager()->can('viewAny', Expense::class))->toBeTrue();
});

test('kitchen staff cannot viewAny expenses', function () {
    expect(makeKitchenStaff()->can('viewAny', Expense::class))->toBeFalse();
});

test('manager can create expenses', function () {
    expect(makeManager()->can('create', Expense::class))->toBeTrue();
});

test('kitchen staff cannot create expenses', function () {
    expect(makeKitchenStaff()->can('create', Expense::class))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Update rules
|--------------------------------------------------------------------------
*/

test('manager can update an active expense', function () {
    $expense = makeExpense();
    expect(makeManager()->can('update', $expense))->toBeTrue();
});

test('cannot update a voided expense', function () {
    $expense = makeExpense([
        'voided_at'    => now(),
        'void_reason'  => 'Duplicate',
    ]);

    expect(makeAdmin()->can('update', $expense))->toBeFalse();
});

test('cannot update a GR-linked expense', function () {
    $gr = makeGoodsReceipt();
    $expense = makeExpense(['goods_receipt_id' => $gr->id]);

    expect(makeAdmin()->can('update', $expense))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Delete rules (drafts only)
|--------------------------------------------------------------------------
*/

test('draft expense with no receipt can be deleted', function () {
    $expense = makeExpense(['status' => Expense::STATUS_DRAFT]);
    expect(makeAdmin()->can('delete', $expense))->toBeTrue();
});

test('paid expense cannot be hard-deleted', function () {
    $expense = makeExpense(['status' => Expense::STATUS_PAID]);
    expect(makeAdmin()->can('delete', $expense))->toBeFalse();
});

test('approved expense cannot be hard-deleted', function () {
    $expense = makeExpense(['status' => Expense::STATUS_APPROVED]);
    expect(makeAdmin()->can('delete', $expense))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Void rules
|--------------------------------------------------------------------------
*/

test('manager can void an active expense', function () {
    $expense = makeExpense();
    expect(makeManager()->can('void', $expense))->toBeTrue();
});

test('cannot void an already voided expense', function () {
    $expense = makeExpense([
        'voided_at'    => now(),
        'void_reason'  => 'Already done',
    ]);

    expect(makeAdmin()->can('void', $expense))->toBeFalse();
});

test('kitchen staff cannot void expenses', function () {
    $expense = makeExpense();
    expect(makeKitchenStaff()->can('void', $expense))->toBeFalse();
});