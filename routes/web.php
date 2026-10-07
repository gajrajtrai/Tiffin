<?php

use App\Http\Controllers\DashboardRedirectController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Starter Kit Routes
|--------------------------------------------------------------------------
*/

// Keep Fortify's settings routes loaded
require __DIR__.'/settings.php';

// The starter kit's /dashboard view is replaced with a role-aware redirect.
// Customers go home; staff go to the admin dashboard.
//
// Note: this file is auto-wrapped in the 'web' middleware group by
// bootstrap/app.php (withRouting(web: ...)), so no explicit 'web' here.
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', DashboardRedirectController::class)
        ->name('dashboard');
});