<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DemoFreshCommand extends Command
{
    protected $signature = 'demo:fresh';
    protected $description = 'Wipe demo data and re-seed with fresh, current-day examples';

    public function handle(): int
    {
        $this->info('Wiping demo data…');

        DB::table('stock_movements')->delete();
        DB::table('goods_receipt_items')->delete();
        DB::table('goods_receipts')->delete();
        DB::table('purchase_order_items')->delete();
        DB::table('purchase_orders')->delete();
        DB::table('order_items')->delete();
        DB::table('orders')->delete();
        DB::table('wallet_transactions')->delete();
        DB::table('payment_proofs')->delete();
        DB::table('daily_menu')->delete();
        DB::table('expenses')->delete();
        DB::table('expense_categories')->delete();

        $demoMobiles = ['+97517111101', '+97517111102', '+97517111103', '+97517111104', '+97517111105'];
        User::whereIn('mobile', $demoMobiles)->forceDelete();

        // Clear the master catalogs the seeder recreates
        \App\Modules\Menu\Models\MenuItem::withTrashed()->forceDelete();
        \App\Modules\Inventory\Models\InventoryItem::withTrashed()->forceDelete();
        \App\Modules\Supplier\Models\Supplier::withTrashed()->forceDelete();

        $this->info('Running DemoSeeder…');
        $this->call('db:seed', ['--class' => 'DemoSeeder']);

        $this->info('Done.');
        return self::SUCCESS;
    }
}