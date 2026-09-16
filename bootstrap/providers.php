<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Application Service Providers
    |--------------------------------------------------------------------------
    */

    AppServiceProvider::class,
    FortifyServiceProvider::class,

    /*
    |--------------------------------------------------------------------------
    | Tamkulay Tiffins Modules
    |--------------------------------------------------------------------------
    */

    App\Modules\Core\Providers\CoreServiceProvider::class,
    App\Modules\Auth\Providers\AuthServiceProvider::class,
    App\Modules\Customer\Providers\CustomerServiceProvider::class,
    App\Modules\Menu\Providers\MenuServiceProvider::class,
    App\Modules\Order\Providers\OrderServiceProvider::class,
    App\Modules\Inventory\Providers\InventoryServiceProvider::class,
    App\Modules\Supplier\Providers\SupplierServiceProvider::class,
    App\Modules\Payment\Providers\PaymentServiceProvider::class,
    App\Modules\Expense\Providers\ExpenseServiceProvider::class,
    App\Modules\Report\Providers\ReportServiceProvider::class,
    App\Modules\Admin\Providers\AdminServiceProvider::class,

];