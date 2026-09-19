<?php

use App\Modules\Admin\Http\Livewire\DailyPublisher;
use App\Modules\Admin\Http\Livewire\Dashboard;
use App\Modules\Admin\Http\Livewire\GoodsReceiptDetail;
use App\Modules\Admin\Http\Livewire\GoodsReceiptsIndex;
use App\Modules\Admin\Http\Livewire\InventoryItemDetail;
use App\Modules\Admin\Http\Livewire\InventoryItemForm;
use App\Modules\Admin\Http\Livewire\InventoryItemsIndex;
use App\Modules\Admin\Http\Livewire\KitchenBoard;
use App\Modules\Admin\Http\Livewire\MenuItemForm;
use App\Modules\Admin\Http\Livewire\MenuItemsIndex;
use App\Modules\Admin\Http\Livewire\OrderDetail;
use App\Modules\Admin\Http\Livewire\OrdersIndex;
use App\Modules\Admin\Http\Livewire\PaymentsIndex;
use App\Modules\Admin\Http\Livewire\PurchaseOrderDetail;
use App\Modules\Admin\Http\Livewire\PurchaseOrderForm;
use App\Modules\Admin\Http\Livewire\PurchaseOrdersIndex;
use App\Modules\Admin\Http\Livewire\SupplierForm;
use App\Modules\Admin\Http\Livewire\SuppliersIndex;
use App\Modules\Admin\Http\Livewire\UserDetail;
use App\Modules\Admin\Http\Livewire\UserForm;
use App\Modules\Admin\Http\Livewire\UsersIndex;
use App\Modules\Admin\Http\Livewire\WeeklyPublisher;
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

        // Users
        Route::get('/users', UsersIndex::class)->name('users.index');
        Route::get('/users/create', UserForm::class)->name('users.create');
        Route::get('/users/{user}', UserDetail::class)->name('users.show');
        Route::get('/users/{user}/edit', UserForm::class)->name('users.edit');

        // Menu
        Route::get('/menu', MenuItemsIndex::class)->name('menu.index');
        Route::get('/menu/create', MenuItemForm::class)->name('menu.create');
        Route::get('/menu/daily', DailyPublisher::class)->name('menu.daily');
        Route::get('/menu/weekly', WeeklyPublisher::class)->name('menu.weekly');
        Route::get('/menu/{item}/edit', MenuItemForm::class)->name('menu.edit');

        // Orders
        Route::get('/orders', OrdersIndex::class)->name('orders.index');
        Route::get('/orders/kitchen', KitchenBoard::class)->name('orders.kitchen');
        Route::get('/orders/{order}', OrderDetail::class)->name('orders.show');

        // Payments
        Route::get('/payments', PaymentsIndex::class)->name('payments.index');

        // Inventory
        Route::get('/inventory', InventoryItemsIndex::class)->name('inventory.index');
        Route::get('/inventory/create', InventoryItemForm::class)->name('inventory.create');
        Route::get('/inventory/{item}', InventoryItemDetail::class)->name('inventory.show');
        Route::get('/inventory/{item}/edit', InventoryItemForm::class)->name('inventory.edit');

        // Suppliers
        Route::get('/suppliers', SuppliersIndex::class)->name('suppliers.index');
        Route::get('/suppliers/create', SupplierForm::class)->name('suppliers.create');
        Route::get('/suppliers/{supplier}/edit', SupplierForm::class)->name('suppliers.edit');

        // Purchase Orders
        Route::get('/purchases', PurchaseOrdersIndex::class)->name('purchases.index');
        Route::get('/purchases/create', PurchaseOrderForm::class)->name('purchases.create');
        Route::get('/purchases/{po}', PurchaseOrderDetail::class)->name('purchases.show');
        Route::get('/purchases/{po}/edit', PurchaseOrderForm::class)->name('purchases.edit');

        // Goods Receipts
        Route::get('/receipts', GoodsReceiptsIndex::class)->name('receipts.index');
        Route::get('/receipts/{gr}', GoodsReceiptDetail::class)->name('receipts.show');
    });