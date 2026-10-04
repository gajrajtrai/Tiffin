<?php

use App\Modules\Payment\Models\WalletTransaction;
use App\Modules\Payment\Services\WalletService;

beforeEach(function () {
    seedRolesAndPermissions();
});

test('credit increases balance and records a ledger entry', function () {
    $customer = makeCustomer(['wallet_balance' => 0]);
    $service = app(WalletService::class);

    $txn = $service->credit($customer, 500, 'Initial top-up');

    expect($txn->type)->toBe(WalletTransaction::TYPE_CREDIT);
    expect((float) $txn->balance_before)->toBe(0.0);
    expect((float) $txn->balance_after)->toBe(500.0);
    expect((float) $customer->fresh()->wallet_balance)->toBe(500.0);
});

test('credit chains balances across multiple transactions', function () {
    $customer = makeCustomer(['wallet_balance' => 0]);
    $service = app(WalletService::class);

    $service->credit($customer, 500, 'First');
    $txn2 = $service->credit($customer, 200, 'Second');

    expect((float) $txn2->balance_before)->toBe(500.0);
    expect((float) $txn2->balance_after)->toBe(700.0);
    expect((float) $customer->fresh()->wallet_balance)->toBe(700.0);
});

test('credit rejects non-positive amounts', function () {
    $customer = makeCustomer(['wallet_balance' => 0]);
    $service = app(WalletService::class);

    expect(fn () => $service->credit($customer, 0, 'Zero'))
        ->toThrow(RuntimeException::class, 'Credit amount must be greater than zero.');

    expect(fn () => $service->credit($customer, -100, 'Negative'))
        ->toThrow(RuntimeException::class, 'Credit amount must be greater than zero.');

    expect((float) $customer->fresh()->wallet_balance)->toBe(0.0);
});

test('debit decreases balance and records a ledger entry', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);
    $service = app(WalletService::class);

    $txn = $service->debit($customer, 150, 'Purchase');

    expect($txn->type)->toBe(WalletTransaction::TYPE_DEBIT);
    expect((float) $txn->balance_before)->toBe(500.0);
    expect((float) $txn->balance_after)->toBe(350.0);
    expect((float) $customer->fresh()->wallet_balance)->toBe(350.0);
});

test('debit rejects insufficient balance without changing wallet or creating ledger entry', function () {
    $customer = makeCustomer(['wallet_balance' => 100]);
    $service = app(WalletService::class);

    expect(fn () => $service->debit($customer, 500, 'Overdraft'))
        ->toThrow(RuntimeException::class);

    expect((float) $customer->fresh()->wallet_balance)->toBe(100.0);
    expect(WalletTransaction::count())->toBe(0);
});

test('debit rejects non-positive amounts', function () {
    $customer = makeCustomer(['wallet_balance' => 100]);
    $service = app(WalletService::class);

    expect(fn () => $service->debit($customer, 0, 'Zero'))
        ->toThrow(RuntimeException::class, 'Debit amount must be greater than zero.');
});

test('refund increases balance and marks ledger entry as refund', function () {
    $customer = makeCustomer(['wallet_balance' => 100]);
    $service = app(WalletService::class);

    $txn = $service->refund($customer, 50, 'Order cancellation');

    expect($txn->type)->toBe(WalletTransaction::TYPE_REFUND);
    expect((float) $txn->balance_before)->toBe(100.0);
    expect((float) $txn->balance_after)->toBe(150.0);
    expect((float) $customer->fresh()->wallet_balance)->toBe(150.0);
});

test('refund records the performed_by user when given', function () {
    $customer = makeCustomer(['wallet_balance' => 100]);
    $admin = makeAdmin();
    $service = app(WalletService::class);

    $txn = $service->refund($customer, 50, 'Refund', null, $admin);

    expect($txn->created_by)->toBe($admin->id);
});