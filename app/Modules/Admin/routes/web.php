<?php

use App\Modules\Admin\Http\Livewire\Dashboard;
use App\Modules\Admin\Http\Livewire\UsersIndex;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Module Routes
|--------------------------------------------------------------------------
|
| The "web" middleware group is required for session, cookies, and CSRF.
| Module routes loaded via service providers do NOT get it automatically.
|
*/

Route::middleware(['web', 'auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', Dashboard::class)->name('dashboard');
        Route::get('/users', UsersIndex::class)->name('users.index');
    });