<?php

use App\Modules\Order\Models\Order;

beforeEach(function () {
    openToday();
});

test('order_number uses YYMMDD-NNN format', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    $order = app(\App\Modules\Order\Services\OrderPlacementService::class)
        ->place($customer, [$item->id => 1], Order::METHOD_PICKUP);

    expect($order->order_number)->toMatch('/^\d{6}-\d{3}$/');
    expect(strlen($order->order_number))->toBe(10);
});

test('display_ref combines month letter and unpadded sequence', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    $order = app(\App\Modules\Order\Services\OrderPlacementService::class)
        ->place($customer, [$item->id => 1], Order::METHOD_PICKUP);

    $monthLetter = chr(64 + (int) now()->format('n'));

    expect($order->display_ref)->toBe($monthLetter.'1'); // First order today = J1
});

test('display_ref strips leading zeros from sequence', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    // Create 12 orders (same customer can't; use separate customers)
    for ($i = 0; $i < 11; $i++) {
        $c = makeCustomer(['wallet_balance' => 500]);
        app(\App\Modules\Order\Services\OrderPlacementService::class)
            ->place($c, [$item->id => 1], Order::METHOD_PICKUP);
    }

    $c12 = makeCustomer(['wallet_balance' => 500]);
    $order12 = app(\App\Modules\Order\Services\OrderPlacementService::class)
        ->place($c12, [$item->id => 1], Order::METHOD_PICKUP);

    expect($order12->order_number)->toBe(now()->format('ymd').'-012');
    expect($order12->display_ref)->toBe(chr(64 + (int) now()->format('n')).'12');
});

test('sequence is per day, not global', function () {
    // Today's first order
    $c1 = makeCustomer(['wallet_balance' => 500]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    $todayOrder = app(\App\Modules\Order\Services\OrderPlacementService::class)
        ->place($c1, [$item->id => 1], Order::METHOD_PICKUP);

    // Yesterday's first order — should also be sequence 1
    $yesterday = today()->subDay();
    \App\Modules\Menu\Models\ServiceDay::updateOrCreate(
        ['service_date' => $yesterday->toDateString()],
        ['is_open' => true, 'delivery_cutoff_time' => '23:59']
    );

    // Create order manually with yesterday's date
    $yOrder = Order::create([
        'user_id' => $c1->id,
        'service_date' => $yesterday,
        'delivery_method' => Order::METHOD_PICKUP,
        'total' => 100,
        'status' => Order::STATUS_DELIVERED,
        'payment_status' => 'paid',
    ]);

    expect($yOrder->order_number)->toBe($yesterday->format('ymd').'-001');
    expect($todayOrder->order_number)->toBe(today()->format('ymd').'-001');
});