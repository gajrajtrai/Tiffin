<?php

use App\Modules\Customer\Http\Livewire\WalletIndex;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer (Public Site) Module Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('public.home');
})->name('home');

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/wallet', WalletIndex::class)->name('wallet.index');
});