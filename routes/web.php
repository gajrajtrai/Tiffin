<?php

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
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();

        if ($user && $user->isStaff()) {
            return redirect()->route('admin.dashboard');
        }

        return redirect('/');
    })->name('dashboard');
});