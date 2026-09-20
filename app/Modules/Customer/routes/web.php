<?php

use App\Modules\Customer\Http\Livewire\CustomerOrderDetail;
use App\Modules\Customer\Http\Livewire\CustomerOrdersIndex;
use App\Modules\Customer\Http\Livewire\MenuBrowse;
use App\Modules\Customer\Http\Livewire\WalletIndex;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer (Public Site) Module Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['web'])->group(function () {

    Route::get('/', function () {
        return view('public.home');
    })->name('home');

    Route::get('/menu', MenuBrowse::class)->name('menu.index');

    Route::middleware(['auth'])->group(function () {
        Route::get('/wallet', WalletIndex::class)->name('wallet.index');
        Route::get('/orders', CustomerOrdersIndex::class)->name('orders.index');
        Route::get('/orders/{order}', CustomerOrderDetail::class)->name('orders.show');
    });
});