<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Expense\Models\Expense;
use App\Modules\Expense\Models\ExpenseCategory;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Inventory\Services\StockService;
use App\Modules\Menu\Models\DailyMenu;
use App\Modules\Menu\Models\MenuItem;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Payment\Models\PaymentProof;
use App\Modules\Payment\Services\WalletService;
use App\Modules\Supplier\Models\Supplier;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Guard: only run if empty
        if (Order::query()->exists()) {
            $this->command->warn('Demo data already exists. Skipping.');
            return;
        }

        $wallet = app(WalletService::class);
        $stock  = app(StockService::class);
        $admin  = User::where('email', 'admin@tamkulay.test')->first();

        if (! $admin) {
            $this->command->error('Run AdminUserSeeder first.');
            return;
        }

        // ─── 1. Menu items ────────────────────────────────────────
        $mains = [
            ['name' => 'Veg Thali',     'is_veg' => true,  'price' => 120],
            ['name' => 'Chicken Thali', 'is_veg' => false, 'price' => 180],
            ['name' => 'Paneer Thali',  'is_veg' => true,  'price' => 150],
        ];
        $fastfood = [
            ['name' => 'Veg Momos (6 pcs)',     'is_veg' => true,  'price' => 80],
            ['name' => 'Chicken Momos (6 pcs)', 'is_veg' => false, 'price' => 100],
            ['name' => 'Veg Chowmein',          'is_veg' => true,  'price' => 90],
            ['name' => 'Chicken Chowmein',      'is_veg' => false, 'price' => 110],
            ['name' => 'Cold Drink',            'is_veg' => true,  'price' => 40],
            ['name' => 'Water Bottle',          'is_veg' => true,  'price' => 20],
        ];

        $menuItems = [];
        foreach ($mains as $i => $data) {
            $menuItems[] = MenuItem::create(array_merge($data, [
                'type' => 'main',
                'sort_order' => $i,
            ]));
        }
        foreach ($fastfood as $i => $data) {
            $menuItems[] = MenuItem::create(array_merge($data, [
                'type' => 'fastfood',
                'sort_order' => $i + 10,
            ]));
        }

        // ─── 2. Publish today's menu (max 2 mains) ────────────────
        $mainIds = collect($menuItems)->where('type', 'main')->take(2)->pluck('id')->all();
        $fastIds = collect($menuItems)->where('type', 'fastfood')->pluck('id')->all();
        DailyMenu::publish(today(), array_merge($mainIds, $fastIds));

        // ─── 3. Customers + wallet top-ups ────────────────────────
        $customers = [
            ['name' => 'Sonam Wangchuk', 'mobile' => '+97517111101', 'topup' => 2000],
            ['name' => 'Pema Choden',    'mobile' => '+97517111102', 'topup' => 3000],
            ['name' => 'Tashi Dorji',    'mobile' => '+97517111103', 'topup' => 1500],
            ['name' => 'Karma Wangmo',   'mobile' => '+97517111104', 'topup' => 2500],
            ['name' => 'Dechen Zangmo',  'mobile' => '+97517111105', 'topup' => 1000],
        ];

        $users = [];
        foreach ($customers as $data) {
            $user = User::create([
                'name'     => $data['name'],
                'mobile'   => $data['mobile'],
                'email'    => null,
                'password' => 'demo1234',
                'status'   => 'active',
            ]);
            $user->assignRole('Customer');
            $wallet->credit($user, $data['topup'], 'Initial wallet top-up');
            $users[] = $user->fresh();
        }

        // ─── 4. Orders ────────────────────────────────────────────
        $todayMains = MenuItem::whereIn('id', $mainIds)->get();
        $todayFast  = MenuItem::whereIn('id', $fastIds)->take(3)->get();

        $orders = [];
        foreach ($users as $idx => $user) {
            $items = [];
            if ($idx < 3) {
                $items[] = $todayMains->random();
            }
            $items[] = $todayFast->random();

            $total = collect($items)->sum('price');

            $order = Order::create([
                'user_id'         => $user->id,
                'service_date'    => today(),
                'delivery_method' => $idx % 3 === 0 ? 'pickup' : 'delivery',
                'delivery_slot'   => $idx % 3 === 0 ? null : '11:00 AM – 2:00 PM',
                'total'           => $total,
                'status'          => Order::STATUS_PENDING,
            ]);

            foreach ($items as $item) {
                OrderItem::create([
                    'order_id'     => $order->id,
                    'menu_item_id' => $item->id,
                    'item_name'    => $item->name,
                    'item_price'   => $item->price,
                    'is_veg'       => $item->is_veg,
                    'item_type'    => $item->type,
                ]);
            }

            $txn = $wallet->debit($user, $total, 'Order '.$order->order_number, $order);
            $order->wallet_transaction_id = $txn->id;
            $order->save();

            $orders[] = $order->fresh();
        }

        // Advance a few orders to different statuses
        if (count($orders) > 2) {
            $orders[0]->status = Order::STATUS_PREPARING;
            $orders[0]->save();
            $orders[1]->status = Order::STATUS_READY;
            $orders[1]->save();
            $orders[2]->status = Order::STATUS_DELIVERED;
            $orders[2]->save();
        }

        // ─── 5. Payment proofs (pending verification) ─────────────
        PaymentProof::create([
            'user_id'        => $users[3]->id,
            'claimed_amount' => 500,
            'bank_reference' => 'MB2026091700042',
            'note'           => 'Paid via mBoB',
            'status'         => PaymentProof::STATUS_PENDING,
        ]);
        PaymentProof::create([
            'user_id'        => $users[4]->id,
            'claimed_amount' => 1000,
            'bank_reference' => 'MP2026091700088',
            'note'           => 'MyPay transfer',
            'status'         => PaymentProof::STATUS_PENDING,
        ]);

        // ─── 6. Inventory items + stock movements ─────────────────
        $rice = InventoryItem::create([
            'name' => 'Basmati Rice', 'category' => 'dry_goods', 'unit' => 'kg',
            'current_stock' => 0, 'reorder_level' => 10, 'unit_cost' => 90,
        ]);
        $oil = InventoryItem::create([
            'name' => 'Cooking Oil', 'category' => 'dry_goods', 'unit' => 'L',
            'current_stock' => 0, 'reorder_level' => 5, 'unit_cost' => 180,
        ]);
        $paneer = InventoryItem::create([
            'name' => 'Paneer', 'category' => 'dairy', 'unit' => 'kg',
            'current_stock' => 0, 'reorder_level' => 3, 'unit_cost' => 350,
        ]);
        $veg = InventoryItem::create([
            'name' => 'Mixed Vegetables', 'category' => 'vegetables', 'unit' => 'kg',
            'current_stock' => 0, 'reorder_level' => 8, 'unit_cost' => 60,
        ]);

        $stock->stockIn($rice,   50,  'Initial purchase', 90,  null, $admin);
        $stock->stockIn($oil,    20,  'Initial purchase', 180, null, $admin);
        $stock->stockIn($paneer, 2,   'Initial purchase', 350, null, $admin);  // below reorder
        $stock->stockIn($veg,    15,  'Initial purchase', 60,  null, $admin);

        $stock->stockOut($rice, 5, 'Lunch prep', null, $admin);
        $stock->stockOut($veg,  4, 'Lunch prep', null, $admin);
        $stock->waste($paneer, 0.3, 'Spoiled', $admin);

        // ─── 7. Supplier ──────────────────────────────────────────
        Supplier::create([
            'name'           => 'Thimphu Fresh Produce',
            'contact_person' => 'Tashi Dorji',
            'mobile'         => '+97517666001',
            'supplies'       => 'vegetables, dairy',
        ]);

        // ─── 8. Expense categories + expenses ─────────────────────
        $rawCat  = ExpenseCategory::create(['name' => 'Raw Materials', 'color' => 'brand', 'sort_order' => 1]);
        $utilCat = ExpenseCategory::create(['name' => 'Utilities',     'color' => 'sky',   'sort_order' => 2]);
        ExpenseCategory::create(['name' => 'Rent',           'color' => 'rose',   'sort_order' => 3]);
        ExpenseCategory::create(['name' => 'Gas',            'color' => 'amber',  'sort_order' => 4]);
        ExpenseCategory::create(['name' => 'Packaging',      'color' => 'emerald','sort_order' => 5]);

        Expense::create([
            'expense_category_id' => $rawCat->id,
            'expense_date'        => today()->subDays(2),
            'description'         => 'Vegetables purchase',
            'amount'              => 1200,
            'payment_method'      => Expense::METHOD_CASH,
            'status'              => Expense::STATUS_PAID,
            'recorded_by'         => $admin->id,
        ]);
        Expense::create([
            'expense_category_id' => $utilCat->id,
            'expense_date'        => today(),
            'description'         => 'Electricity bill',
            'amount'              => 850,
            'payment_method'      => Expense::METHOD_BANK_TRANSFER,
            'status'              => Expense::STATUS_PAID,
            'recorded_by'         => $admin->id,
        ]);

        // ─── Summary ──────────────────────────────────────────────
        $this->command->info('✓ Demo data seeded:');
        $this->command->info('  · '.count($menuItems).' menu items');
        $this->command->info('  · '.count($users).' customers with wallet top-ups');
        $this->command->info('  · '.count($orders).' orders for today');
        $this->command->info('  · 2 pending payment proofs');
        $this->command->info('  · 4 inventory items with stock movements');
        $this->command->info('  · 1 supplier');
        $this->command->info('  · 5 expense categories, 2 expenses');
    }
}