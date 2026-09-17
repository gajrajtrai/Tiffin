<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Starter Kit Routes
|--------------------------------------------------------------------------
|
| The root "/" route is owned by the Customer module
| (app/Modules/Customer/routes/web.php) so the Tamkulay Tiffins homepage
| is served instead of the default welcome view.
|
| Keep only the settings routes here, which are provided by the Livewire
| starter kit.
|
*/

Route::middleware(['auth'])->group(function () {
    Route::view('settings/appearance', 'settings.appearance')->name('appearance.edit');
});