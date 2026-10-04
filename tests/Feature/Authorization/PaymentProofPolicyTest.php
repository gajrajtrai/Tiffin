<?php

use App\Modules\Payment\Models\PaymentProof;

function makeProof(array $attributes = []): PaymentProof
{
    return PaymentProof::create(array_merge([
        'user_id'        => makeCustomer()->id,
        'claimed_amount' => 500,
        'status'         => PaymentProof::STATUS_PENDING,
    ], $attributes));
}

/*
|--------------------------------------------------------------------------
| Permission layer
|--------------------------------------------------------------------------
*/

test('admin can viewAny proofs', function () {
    expect(makeAdmin()->can('viewAny', PaymentProof::class))->toBeTrue();
});

test('manager can viewAny proofs', function () {
    expect(makeManager()->can('viewAny', PaymentProof::class))->toBeTrue();
});

test('kitchen staff cannot viewAny proofs', function () {
    expect(makeKitchenStaff()->can('viewAny', PaymentProof::class))->toBeFalse();
});

test('customer can view their own proof', function () {
    $customer = makeCustomer();
    $proof = PaymentProof::create([
        'user_id'        => $customer->id,
        'claimed_amount' => 500,
        'status'         => PaymentProof::STATUS_PENDING,
    ]);

    expect($customer->can('view', $proof))->toBeTrue();
});

test('customer cannot view another customer proof', function () {
    $customer1 = makeCustomer();
    $customer2 = makeCustomer();
    $proof = PaymentProof::create([
        'user_id'        => $customer1->id,
        'claimed_amount' => 500,
        'status'         => PaymentProof::STATUS_PENDING,
    ]);

    expect($customer2->can('view', $proof))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Verify (approve)
|--------------------------------------------------------------------------
*/

test('admin can verify a pending proof', function () {
    $proof = makeProof(['status' => PaymentProof::STATUS_PENDING]);
    expect(makeAdmin()->can('verify', $proof))->toBeTrue();
});

test('manager can verify a pending proof', function () {
    $proof = makeProof(['status' => PaymentProof::STATUS_PENDING]);
    expect(makeManager()->can('verify', $proof))->toBeTrue();
});

test('kitchen staff cannot verify a proof', function () {
    $proof = makeProof(['status' => PaymentProof::STATUS_PENDING]);
    expect(makeKitchenStaff()->can('verify', $proof))->toBeFalse();
});

test('cannot verify an already-approved proof', function () {
    $proof = makeProof(['status' => PaymentProof::STATUS_APPROVED]);
    expect(makeAdmin()->can('verify', $proof))->toBeFalse();
});

test('cannot verify a rejected proof', function () {
    $proof = makeProof(['status' => PaymentProof::STATUS_REJECTED]);
    expect(makeAdmin()->can('verify', $proof))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Reject
|--------------------------------------------------------------------------
*/

test('admin can reject a pending proof', function () {
    $proof = makeProof(['status' => PaymentProof::STATUS_PENDING]);
    expect(makeAdmin()->can('reject', $proof))->toBeTrue();
});

test('manager can reject a pending proof', function () {
    $proof = makeProof(['status' => PaymentProof::STATUS_PENDING]);
    expect(makeManager()->can('reject', $proof))->toBeTrue();
});

test('cannot reject an already-reviewed proof', function () {
    $approved = makeProof(['status' => PaymentProof::STATUS_APPROVED]);
    $rejected = makeProof(['status' => PaymentProof::STATUS_REJECTED]);

    expect(makeAdmin()->can('reject', $approved))->toBeFalse();
    expect(makeAdmin()->can('reject', $rejected))->toBeFalse();
});