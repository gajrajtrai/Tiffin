cd C:\Users\RAIPC\tiffin-app

$modules = @('Core','Auth','Customer','Menu','Order','Inventory','Supplier','Payment','Expense','Report','Admin')
foreach ($m in $modules) {
    $path = "app\Modules\$m\resources\views"
    New-Item -ItemType Directory -Force -Path $path | Out-Null
    New-Item -ItemType File -Force -Path "$path\.gitkeep" | Out-Null
}

git add .
git commit -m "Add .gitkeep files for module view folders (empty folders are not tracked by Git)"
git push