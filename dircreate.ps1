$modules = @('Core','Auth','Customer','Menu','Order','Inventory','Supplier','Payment','Expense','Report','Admin')
$base = "app\Modules"
New-Item -ItemType Directory -Force -Path $base | Out-Null

foreach ($m in $modules) {
    $p = "$base\$m"
    New-Item -ItemType Directory -Force -Path "$p\Providers"            | Out-Null
    New-Item -ItemType Directory -Force -Path "$p\routes"               | Out-Null
    New-Item -ItemType Directory -Force -Path "$p\Models"               | Out-Null
    New-Item -ItemType Directory -Force -Path "$p\Http\Controllers"     | Out-Null
    New-Item -ItemType Directory -Force -Path "$p\Http\Livewire"        | Out-Null
    New-Item -ItemType Directory -Force -Path "$p\Http\Requests"        | Out-Null
    New-Item -ItemType Directory -Force -Path "$p\resources\views"      | Out-Null

    $provider = @"
<?php

namespace App\Modules\$m\Providers;

use Illuminate\Support\ServiceProvider;

class ${m}ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        `$this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        `$this->loadViewsFrom(__DIR__.'/../resources/views', '$($m.ToLower())');
    }
}
"@
    Set-Content -Path "$p\Providers\${m}ServiceProvider.php" -Value $provider

    $routes = @"
<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| $m Module Routes
|--------------------------------------------------------------------------
|
| Routes for the $m module of Tamkulay Tiffins.
|
*/
"@
    Set-Content -Path "$p\routes\web.php" -Value $routes

    Write-Host "Created module: $m"
}