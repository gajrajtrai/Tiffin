<?php

/*
|--------------------------------------------------------------------------
| Authorization
|--------------------------------------------------------------------------
*/

test('guest is redirected from export to login', function () {
    $response = $this->get(route('admin.reports.export', ['type' => 'orders']));

    $response->assertRedirect(route('login'));
});

test('kitchen staff cannot export reports', function () {
    $kitchen = makeKitchenStaff();

    $response = $this->actingAs($kitchen)
        ->get(route('admin.reports.export', ['type' => 'orders']));

    $response->assertForbidden();
});

test('manager can export reports', function () {
    $manager = makeManager();

    $response = $this->actingAs($manager)
        ->get(route('admin.reports.export', ['type' => 'orders']));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});

test('admin can export reports', function () {
    $admin = makeAdmin();

    $response = $this->actingAs($admin)
        ->get(route('admin.reports.export', ['type' => 'orders']));

    $response->assertOk();
});

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

test('unknown export type is rejected with 422', function () {
    $admin = makeAdmin();

    $response = $this->actingAs($admin)
        ->get(route('admin.reports.export', ['type' => 'employees']));

    $response->assertStatus(422);
});

test('missing type is rejected with 422', function () {
    $admin = makeAdmin();

    $response = $this->actingAs($admin)
        ->get(route('admin.reports.export'));

    $response->assertStatus(422);
});

test('malformed from date is rejected with 422', function () {
    $admin = makeAdmin();

    $response = $this->actingAs($admin)
        ->get(route('admin.reports.export', [
            'type' => 'orders',
            'from' => 'not-a-date',
        ]));

    $response->assertStatus(422);
});

test('to before from is rejected with 422', function () {
    $admin = makeAdmin();

    $response = $this->actingAs($admin)
        ->get(route('admin.reports.export', [
            'type' => 'orders',
            'from' => today()->toDateString(),
            'to'   => today()->subDay()->toDateString(),
        ]));

    $response->assertStatus(422);
});

test('from more than a year ago is rejected', function () {
    $admin = makeAdmin();

    $response = $this->actingAs($admin)
        ->get(route('admin.reports.export', [
            'type' => 'orders',
            'from' => now()->subYear()->subDay()->toDateString(),
        ]));

    $response->assertStatus(422);
});

test('future from date is rejected', function () {
    $admin = makeAdmin();

    $response = $this->actingAs($admin)
        ->get(route('admin.reports.export', [
            'type' => 'orders',
            'from' => now()->addDay()->toDateString(),
        ]));

    $response->assertStatus(422);
});

test('future to date is rejected', function () {
    $admin = makeAdmin();

    $response = $this->actingAs($admin)
        ->get(route('admin.reports.export', [
            'type' => 'orders',
            'to' => now()->addDay()->toDateString(),
        ]));

    $response->assertStatus(422);
});

/*
|--------------------------------------------------------------------------
| Valid exports succeed
|--------------------------------------------------------------------------
*/

test('orders export with no dates uses this month', function () {
    $admin = makeAdmin();

    $response = $this->actingAs($admin)
        ->get(route('admin.reports.export', ['type' => 'orders']));

    $response->assertOk();
    $response->assertDownload();
});

test('items export succeeds', function () {
    $admin = makeAdmin();

    $response = $this->actingAs($admin)
        ->get(route('admin.reports.export', ['type' => 'items']));

    $response->assertOk();
});

test('customers export succeeds', function () {
    $admin = makeAdmin();

    $response = $this->actingAs($admin)
        ->get(route('admin.reports.export', ['type' => 'customers']));

    $response->assertOk();
});

test('expenses export succeeds', function () {
    $admin = makeAdmin();

    $response = $this->actingAs($admin)
        ->get(route('admin.reports.export', ['type' => 'expenses']));

    $response->assertOk();
});

test('explicit date range is accepted', function () {
    $admin = makeAdmin();

    $response = $this->actingAs($admin)
        ->get(route('admin.reports.export', [
            'type' => 'orders',
            'from' => now()->subMonth()->toDateString(),
            'to'   => today()->toDateString(),
        ]));

    $response->assertOk();
});