<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Core Module Routes
|--------------------------------------------------------------------------
|
| Routes for the Core module of Tamkulay Tiffins.
|
*/

Route::get('/__modules-test', function () {
    return response()->json([
        'status'  => 'ok',
        'app'     => config('app.name'),
        'modules' => [
            'Core', 'Auth', 'Customer', 'Menu', 'Order',
            'Inventory', 'Supplier', 'Payment', 'Expense', 'Report', 'Admin',
        ],
        'loaded_at' => now()->toDateTimeString(),
    ]);
});