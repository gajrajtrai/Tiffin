<?php

use App\Modules\Customer\Http\Livewire\MenuBrowse;
use App\Modules\Customer\Http\Livewire\WalletIndex;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer (Public Site) Module Routes
|--------------------------------------------------------------------------
|
| These routes are loaded via a service provider, so they need the "web"
| middleware group explicitly for session, cookies, CSRF, and auth().
|
*/

Route::middleware(['web'])->group(function () {

    Route::get('/', function () {
        return view('public.home');
    })->name('home');

    Route::get('/menu', MenuBrowse::class)->name('menu.index');

    Route::middleware(['auth'])->group(function () {
        Route::get('/wallet', WalletIndex::class)->name('wallet.index');
    });
});