<?php

use App\Modules\Menu\Models\ServiceDay;
use App\Modules\Order\Models\Order;

beforeEach(function () {
    openToday();
});

test('order_number uses YYMMDD-NN format', function () {
    $customer = makeCustomer(['wallet_balance' => 500]);
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    $order = app(\App\Modules\Order\Services\OrderPlacementService::class)
        ->place($customer, [$item->id => 1], Order::METHOD_PICKUP);

    expect($order->order_number)->toMatch('/^\d{6}-\d{2}$/');
    expect(strlen($order->order_number))->toBe(9);
});

test('sequence increments within the same day', function () {
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    $c1 = makeCustomer(['wallet_balance' => 500]);
    $c2 = makeCustomer(['wallet_balance' => 500]);

    $a = app(\App\Modules\Order\Services\OrderPlacementService::class)
        ->place($c1, [$item->id => 1], Order::METHOD_PICKUP);
    $b = app(\App\Modules\Order\Services\OrderPlacementService::class)
        ->place($c2, [$item->id => 1], Order::METHOD_PICKUP);

    expect(str_ends_with($a->order_number, '-01'))->toBeTrue();
    expect(str_ends_with($b->order_number, '-02'))->toBeTrue();
});

test('sequence is per day, not global', function () {
    $item = makeMenuItem(['price' => 100]);
    publishToday($item);

    // Today's first order
    $c1 = makeCustomer(['wallet_balance' => 500]);
    $todayOrder = app(\App\Modules\Order\Services\OrderPlacementService::class)
        ->place($c1, [$item->id => 1], Order::METHOD_PICKUP);

    // Yesterday's manual order — should also be sequence 01
    $yesterday = today()->subDay();

    ServiceDay::updateOrCreate(
        ['service_date' => $yesterday->toDateString()],
        ['is_open' => true, 'delivery_cutoff_time' => '23:59']
    );

    $yOrder = Order::create([
        'user_id'         => $c1->id,
        'service_date'    => $yesterday,
        'delivery_method' => Order::METHOD_PICKUP,
        'total'           => 100,
        'status'          => Order::STATUS_DELIVERED,
        'payment_status'  => 'paid',
    ]);

    expect($yOrder->order_number)->toBe($yesterday->format('ymd').'-01');
    expect($todayOrder->order_number)->toBe(today()->format('ymd').'-01');
});