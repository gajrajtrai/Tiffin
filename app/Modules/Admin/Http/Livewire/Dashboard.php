<?php

namespace App\Modules\Admin\Http\Livewire;

use App\Models\User;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Menu\Models\DailyMenu;
use App\Modules\Order\Models\Order;
use App\Modules\Payment\Models\PaymentProof;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Dashboard extends Component
{
    public function layoutData(): array
    {
        return [
            'title'   => 'Dashboard',
            'heading' => 'Dashboard',
        ];
    }

    public function render(): View
    {
        $today = today();

        $stats = [
            'todayOrders'      => Order::forDate($today)->count(),
            'todayRevenue'     => (float) Order::forDate($today)
                                            ->where('status', '!=', Order::STATUS_CANCELLED)
                                            ->sum('total'),
            'pendingPrep'      => Order::forDate($today)
                                            ->whereIn('status', [
                                                Order::STATUS_PENDING,
                                                Order::STATUS_CONFIRMED,
                                                Order::STATUS_PREPARING,
                                            ])->count(),
            'readyCount'       => Order::forDate($today)->where('status', Order::STATUS_READY)->count(),
            'itemsAvailable'   => DailyMenu::whereDate('service_date', $today)->count(),
            'lowStockCount'    => InventoryItem::lowStock()->count(),
            'pendingProofs'    => PaymentProof::pending()->count(),
            'lowBalanceCount'  => User::role('Customer')
                                            ->whereColumn('wallet_balance', '<', 'low_balance_threshold')
                                            ->count(),
        ];

        $orders = Order::with(['user', 'items'])
            ->forDate($today)
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        $lowStockItems = InventoryItem::lowStock()
            ->orderBy('current_stock')
            ->limit(5)
            ->get();

        $pendingProofs = PaymentProof::with('user')
            ->pending()
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $lowBalanceCustomers = User::role('Customer')
            ->whereColumn('wallet_balance', '<', 'low_balance_threshold')
            ->orderBy('wallet_balance')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'orders',
            'lowStockItems',
            'pendingProofs',
            'lowBalanceCustomers',
        ));
    }
}