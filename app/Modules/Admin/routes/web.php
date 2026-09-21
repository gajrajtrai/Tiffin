<?php

use App\Modules\Admin\Http\Controllers\ReportExportController;
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
use App\Modules\Admin\Http\Livewire\ReportsDashboard;
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
|
| Middleware stack:
|   web          — session, cookies, CSRF
|   auth         — must be logged in
|   staff        — customers are redirected to /
|   can:xxx.yyy  — per-route permission check
|
*/

Route::middleware(['web', 'auth', 'staff'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // Dashboard — visible to anyone on staff
        Route::get('/dashboard', Dashboard::class)
            ->middleware('can:order.view')
            ->name('dashboard');

        // Users
        Route::get('/users', UsersIndex::class)
            ->middleware('can:user.view')->name('users.index');
        Route::get('/users/create', UserForm::class)
            ->middleware('can:user.create')->name('users.create');
        Route::get('/users/{user}', UserDetail::class)
            ->middleware('can:user.view')->name('users.show');
        Route::get('/users/{user}/edit', UserForm::class)
            ->middleware('can:user.edit')->name('users.edit');

        // Menu
        Route::get('/menu', MenuItemsIndex::class)
            ->middleware('can:menu.view')->name('menu.index');
        Route::get('/menu/create', MenuItemForm::class)
            ->middleware('can:menu.create')->name('menu.create');
        Route::get('/menu/daily', DailyPublisher::class)
            ->middleware('can:menu.publish')->name('menu.daily');
        Route::get('/menu/weekly', WeeklyPublisher::class)
            ->middleware('can:menu.publish')->name('menu.weekly');
        Route::get('/menu/{item}/edit', MenuItemForm::class)
            ->middleware('can:menu.edit')->name('menu.edit');

        // Orders
        Route::get('/orders', OrdersIndex::class)
            ->middleware('can:order.view')->name('orders.index');
        Route::get('/orders/kitchen', KitchenBoard::class)
            ->middleware('can:order.view')->name('orders.kitchen');
        Route::get('/orders/{order}', OrderDetail::class)
            ->middleware('can:order.view')->name('orders.show');

        // Payments
        Route::get('/payments', PaymentsIndex::class)
            ->middleware('can:payment.view')->name('payments.index');

        // Inventory
        Route::get('/inventory', InventoryItemsIndex::class)
            ->middleware('can:inventory.view')->name('inventory.index');
        Route::get('/inventory/create', InventoryItemForm::class)
            ->middleware('can:inventory.adjust')->name('inventory.create');
        Route::get('/inventory/{item}', InventoryItemDetail::class)
            ->middleware('can:inventory.view')->name('inventory.show');
        Route::get('/inventory/{item}/edit', InventoryItemForm::class)
            ->middleware('can:inventory.adjust')->name('inventory.edit');

        // Suppliers
        Route::get('/suppliers', SuppliersIndex::class)
            ->middleware('can:supplier.view')->name('suppliers.index');
        Route::get('/suppliers/create', SupplierForm::class)
            ->middleware('can:supplier.create')->name('suppliers.create');
        Route::get('/suppliers/{supplier}/edit', SupplierForm::class)
            ->middleware('can:supplier.edit')->name('suppliers.edit');

        // Purchase Orders
        Route::get('/purchases', PurchaseOrdersIndex::class)
            ->middleware('can:purchase.view')->name('purchases.index');
        Route::get('/purchases/create', PurchaseOrderForm::class)
            ->middleware('can:purchase.create')->name('purchases.create');
        Route::get('/purchases/{po}', PurchaseOrderDetail::class)
            ->middleware('can:purchase.view')->name('purchases.show');
        Route::get('/purchases/{po}/edit', PurchaseOrderForm::class)
            ->middleware('can:purchase.edit')->name('purchases.edit');

        // Goods Receipts
        Route::get('/receipts', GoodsReceiptsIndex::class)
            ->middleware('can:purchase.view')->name('receipts.index');
        Route::get('/receipts/{gr}', GoodsReceiptDetail::class)
            ->middleware('can:purchase.view')->name('receipts.show');

        // Reports
        Route::get('/reports', ReportsDashboard::class)
            ->middleware('can:report.view')->name('reports.index');
        Route::get('/reports/export', ReportExportController::class)
            ->middleware('can:report.export')->name('reports.export');

        // Expenses
        Route::get('/expenses', \App\Modules\Admin\Http\Livewire\ExpensesIndex::class)
            ->middleware('can:expense.view')->name('expenses.index');
        Route::get('/expenses/categories', \App\Modules\Admin\Http\Livewire\ExpenseCategoriesIndex::class)
            ->middleware('can:expense.view')->name('expenses.categories');
        Route::get('/expenses/create', \App\Modules\Admin\Http\Livewire\ExpenseForm::class)
            ->middleware('can:expense.create')->name('expenses.create');
        Route::get('/expenses/{expense}/edit', \App\Modules\Admin\Http\Livewire\ExpenseForm::class)
            ->middleware('can:expense.edit')->name('expenses.edit');

        // Settings
        Route::get('/settings', \App\Modules\Admin\Http\Livewire\SettingsIndex::class)
            ->middleware('can:settings.view')->name('settings.index');

        // Audit Log
        Route::get('/audit', \App\Modules\Admin\Http\Livewire\AuditLogIndex::class)
            ->middleware('can:audit.view')->name('audit.index');
    });