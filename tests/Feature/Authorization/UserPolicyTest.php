<?php

/*
|--------------------------------------------------------------------------
| View
|--------------------------------------------------------------------------
*/

test('admin can viewAny users', function () {
    expect(makeAdmin()->can('viewAny', App\Models\User::class))->toBeTrue();
});

test('manager can viewAny users', function () {
    expect(makeManager()->can('viewAny', App\Models\User::class))->toBeTrue();
});

test('kitchen staff cannot viewAny users', function () {
    expect(makeKitchenStaff()->can('viewAny', App\Models\User::class))->toBeFalse();
});

test('anyone can view their own profile', function () {
    $customer = makeCustomer();
    expect($customer->can('view', $customer))->toBeTrue();
});

test('customer cannot view another user', function () {
    $c1 = makeCustomer();
    $c2 = makeCustomer();
    expect($c1->can('view', $c2))->toBeFalse();
});

test('admin can view any user', function () {
    $admin = makeAdmin();
    $customer = makeCustomer();
    expect($admin->can('view', $customer))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Create / Update
|--------------------------------------------------------------------------
*/

test('admin can create users', function () {
    expect(makeAdmin()->can('create', App\Models\User::class))->toBeTrue();
});

test('manager cannot create users', function () {
    expect(makeManager()->can('create', App\Models\User::class))->toBeFalse();
});

test('admin can update users', function () {
    $target = makeCustomer();
    expect(makeAdmin()->can('update', $target))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| toggleStatus — self-suspend guard
|--------------------------------------------------------------------------
*/

test('admin cannot suspend themselves', function () {
    $admin = makeAdmin();
    expect($admin->can('toggleStatus', $admin))->toBeFalse();
});

test('admin can suspend another user', function () {
    $admin = makeAdmin();
    $customer = makeCustomer();
    expect($admin->can('toggleStatus', $customer))->toBeTrue();
});

test('kitchen staff cannot suspend anyone', function () {
    $kitchen = makeKitchenStaff();
    $customer = makeCustomer();
    expect($kitchen->can('toggleStatus', $customer))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Wallet actions
|--------------------------------------------------------------------------
*/

test('manager can credit a wallet', function () {
    $manager = makeManager();
    $customer = makeCustomer();
    expect($manager->can('creditWallet', $customer))->toBeTrue();
});

test('manager can debit a wallet', function () {
    $manager = makeManager();
    $customer = makeCustomer();
    expect($manager->can('debitWallet', $customer))->toBeTrue();
});

test('kitchen staff cannot credit or debit wallets', function () {
    $kitchen = makeKitchenStaff();
    $customer = makeCustomer();
    expect($kitchen->can('creditWallet', $customer))->toBeFalse();
    expect($kitchen->can('debitWallet', $customer))->toBeFalse();
});