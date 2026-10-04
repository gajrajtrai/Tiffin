<?php

beforeEach(function () {
    seedRolesAndPermissions();
});

test('guests are redirected from admin dashboard to login', function () {
    $response = $this->get(route('admin.dashboard'));
    $response->assertRedirect(route('login'));
});

test('customers are redirected from admin to public home', function () {
    $customer = makeCustomer();

    $response = $this->actingAs($customer)->get(route('admin.dashboard'));

    // RedirectCustomersFromAdmin middleware sends customers to /
    $response->assertRedirect('/');
});

test('admin can access admin dashboard', function () {
    $admin = makeAdmin();

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk();
});

test('kitchen staff cannot access the users list', function () {
    $kitchen = makeKitchenStaff();

    $response = $this->actingAs($kitchen)->get(route('admin.users.index'));

    $response->assertForbidden();
});

test('manager can access the users list', function () {
    $manager = makeManager();

    $response = $this->actingAs($manager)->get(route('admin.users.index'));

    $response->assertOk();
});

test('kitchen staff cannot access payment verification', function () {
    $kitchen = makeKitchenStaff();

    $response = $this->actingAs($kitchen)->get(route('admin.payments.index'));

    $response->assertForbidden();
});

test('kitchen staff can access the kitchen board', function () {
    $kitchen = makeKitchenStaff();

    $response = $this->actingAs($kitchen)->get(route('admin.orders.kitchen'));

    $response->assertOk();
});

test('customer cannot access any admin route', function () {
    $customer = makeCustomer();

    foreach ([
        'admin.dashboard',
        'admin.orders.index',
        'admin.menu.index',
        'admin.payments.index',
        'admin.users.index',
    ] as $routeName) {
        $response = $this->actingAs($customer)->get(route($routeName));
        $response->assertRedirect('/');
    }
});