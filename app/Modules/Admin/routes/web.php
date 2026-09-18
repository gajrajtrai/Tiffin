<?php

use App\Modules\Admin\Http\Livewire\Dashboard;
use App\Modules\Admin\Http\Livewire\UserDetail;
use App\Modules\Admin\Http\Livewire\UserForm;
use App\Modules\Admin\Http\Livewire\UsersIndex;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Module Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', Dashboard::class)->name('dashboard');

        Route::get('/users', UsersIndex::class)->name('users.index');
        Route::get('/users/create', UserForm::class)->name('users.create');
        Route::get('/users/{user}', UserDetail::class)->name('users.show');
        Route::get('/users/{user}/edit', UserForm::class)->name('users.edit');
    });