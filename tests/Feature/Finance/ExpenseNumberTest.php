<?php

use App\Modules\Expense\Models\Expense;
use App\Modules\Expense\Models\ExpenseCategory;

function makeNumberCategory(): ExpenseCategory
{
    static $n = 0;
    $n++;

    return ExpenseCategory::create([
        'name'       => 'Number Cat '.$n,
        'slug'       => 'number-cat-'.$n,
        'color'      => 'slate',
        'sort_order' => 0,
        'is_active'  => true,
    ]);
}

test('expense_number uses EYYMMDD-NN format', function () {
    $expense = Expense::create([
        'expense_category_id' => makeNumberCategory()->id,
        'expense_date'        => today(),
        'description'         => 'Test',
        'amount'              => 100,
        'payment_method'      => Expense::METHOD_CASH,
        'status'              => Expense::STATUS_PAID,
    ]);

    expect($expense->expense_number)->toMatch('/^E\d{6}-\d{2}$/');
    expect(strlen($expense->expense_number))->toBe(10);
});

test('expense sequence increments within the same day', function () {
    $cat = makeNumberCategory();

    $a = Expense::create([
        'expense_category_id' => $cat->id,
        'expense_date' => today(),
        'description' => 'First',
        'amount' => 100,
        'payment_method' => Expense::METHOD_CASH,
        'status' => Expense::STATUS_PAID,
    ]);

    $b = Expense::create([
        'expense_category_id' => $cat->id,
        'expense_date' => today(),
        'description' => 'Second',
        'amount' => 100,
        'payment_method' => Expense::METHOD_CASH,
        'status' => Expense::STATUS_PAID,
    ]);

    expect(str_ends_with($a->expense_number, '-01'))->toBeTrue();
    expect(str_ends_with($b->expense_number, '-02'))->toBeTrue();
});

test('expense sequence is per day, not global', function () {
    $cat = makeNumberCategory();

    $todayExp = Expense::create([
        'expense_category_id' => $cat->id,
        'expense_date' => today(),
        'description' => 'Today',
        'amount' => 100,
        'payment_method' => Expense::METHOD_CASH,
        'status' => Expense::STATUS_PAID,
    ]);

    $yesterdayExp = Expense::create([
        'expense_category_id' => $cat->id,
        'expense_date' => today()->subDay(),
        'description' => 'Yesterday',
        'amount' => 100,
        'payment_method' => Expense::METHOD_CASH,
        'status' => Expense::STATUS_PAID,
    ]);

    expect(str_ends_with($todayExp->expense_number, '-01'))->toBeTrue();
    expect(str_ends_with($yesterdayExp->expense_number, '-01'))->toBeTrue();
});