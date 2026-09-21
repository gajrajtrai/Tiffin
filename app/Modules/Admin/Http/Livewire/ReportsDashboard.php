<?php

namespace App\Modules\Admin\Http\Livewire;

use App\Modules\Expense\Models\Expense;
use App\Modules\Expense\Models\ExpenseCategory;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Payment\Models\WalletTransaction;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class ReportsDashboard extends Component
{
    #[Url(as: 'from', except: '')]
    public string $from = '';

    #[Url(as: 'to', except: '')]
    public string $to = '';

    public function mount(): void
    {
        if (! auth()->user()->can('report.view')) {
            abort(403);
        }

        if ($this->from === '') {
            $this->from = now()->startOfMonth()->toDateString();
        }
        if ($this->to === '') {
            $this->to = today()->toDateString();
        }
    }

    public function layoutData(): array
    {
        return [
            'title'   => 'Reports',
            'heading' => 'Reports',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Date range presets
    |--------------------------------------------------------------------------
    */

    public function presetToday(): void
    {
        $this->from = today()->toDateString();
        $this->to = today()->toDateString();
    }

    public function presetThisWeek(): void
    {
        $this->from = now()->startOfWeek()->toDateString();
        $this->to = today()->toDateString();
    }

    public function presetThisMonth(): void
    {
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = today()->toDateString();
    }

    public function presetLastMonth(): void
    {
        $this->from = now()->subMonth()->startOfMonth()->toDateString();
        $this->to = now()->subMonth()->endOfMonth()->toDateString();
    }

    public function presetLast30Days(): void
    {
        $this->from = now()->subDays(29)->toDateString();
        $this->to = today()->toDateString();
    }

    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */

    public function render(): View
    {
        $fromDate = Carbon::parse($this->from)->startOfDay();
        $toDate = Carbon::parse($this->to)->endOfDay();
        $fromStr = $fromDate->toDateString();
        $toStr = $toDate->toDateString();

        /*
        |----------------------------------------------------------------------
        | Sales summary
        |----------------------------------------------------------------------
        */
        $salesQuery = Order::query()
            ->whereBetween('service_date', [$fromStr, $toStr])
            ->where('status', '!=', Order::STATUS_CANCELLED);

        $revenue = (float) (clone $salesQuery)->sum('total');
        $orderCount = (clone $salesQuery)->count();
        $avgOrderValue = $orderCount > 0 ? $revenue / $orderCount : 0;

        $cancelledCount = Order::query()
            ->whereBetween('service_date', [$fromStr, $toStr])
            ->where('status', Order::STATUS_CANCELLED)
            ->count();

        $activeCustomers = (clone $salesQuery)
            ->distinct('user_id')
            ->count('user_id');

        $deliveryCount = (clone $salesQuery)->where('delivery_method', Order::METHOD_DELIVERY)->count();
        $pickupCount = (clone $salesQuery)->where('delivery_method', Order::METHOD_PICKUP)->count();

        /*
        |----------------------------------------------------------------------
        | Expenses
        |----------------------------------------------------------------------
        */
        $expenseTotal = (float) Expense::query()
            ->whereBetween('expense_date', [$fromStr, $toStr])
            ->whereNull('voided_at')
            ->sum('amount');

        $netProfit = $revenue - $expenseTotal;

        /*
        |----------------------------------------------------------------------
        | Wallet activity
        |----------------------------------------------------------------------
        */
        $walletCredits = (float) WalletTransaction::query()
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->where('type', WalletTransaction::TYPE_CREDIT)
            ->sum('amount');

        $walletDebits = (float) WalletTransaction::query()
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->where('type', WalletTransaction::TYPE_DEBIT)
            ->sum('amount');

        $walletRefunds = (float) WalletTransaction::query()
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->where('type', WalletTransaction::TYPE_REFUND)
            ->sum('amount');

        $walletFloat = (float) User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'Customer'))
            ->sum('wallet_balance');

        /*
        |----------------------------------------------------------------------
        | Daily sales series
        |----------------------------------------------------------------------
        */
        $dailySeries = Order::query()
            ->selectRaw('DATE(service_date) as day, SUM(total) as revenue, COUNT(*) as orders')
            ->whereBetween('service_date', [$fromStr, $toStr])
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $dailyPoints = [];
        $cursor = $fromDate->copy();
        while ($cursor->lte($toDate)) {
            $key = $cursor->toDateString();
            $dailyPoints[] = [
                'date' => $key,
                'label' => $cursor->format('M j'),
                'revenue' => (float) ($dailySeries[$key]->revenue ?? 0),
                'orders' => (int) ($dailySeries[$key]->orders ?? 0),
            ];
            $cursor->addDay();
        }

        /*
        |----------------------------------------------------------------------
        | Top items
        |----------------------------------------------------------------------
        */
        $topItems = OrderItem::query()
            ->selectRaw('
                item_name,
                item_type,
                is_veg,
                COUNT(*) as order_count,
                SUM(item_price) as revenue
            ')
            ->whereHas('order', function ($q) use ($fromStr, $toStr) {
                $q->whereBetween('service_date', [$fromStr, $toStr])
                  ->where('status', '!=', Order::STATUS_CANCELLED);
            })
            ->groupBy('item_name', 'item_type', 'is_veg')
            ->orderByDesc('order_count')
            ->limit(10)
            ->get();

        /*
        |----------------------------------------------------------------------
        | Top customers
        |----------------------------------------------------------------------
        */
        $topCustomers = User::query()
            ->selectRaw('users.id, users.name, users.mobile, users.wallet_balance, COUNT(orders.id) as order_count, COALESCE(SUM(orders.total), 0) as total_spend')
            ->join('orders', function ($join) use ($fromStr, $toStr) {
                $join->on('orders.user_id', '=', 'users.id')
                     ->whereBetween('orders.service_date', [$fromStr, $toStr])
                     ->where('orders.status', '!=', Order::STATUS_CANCELLED);
            })
            ->groupBy('users.id', 'users.name', 'users.mobile', 'users.wallet_balance')
            ->orderByDesc('total_spend')
            ->limit(10)
            ->get();

        /*
        |----------------------------------------------------------------------
        | Expense breakdown by category
        |----------------------------------------------------------------------
        */
        $expenseBreakdown = ExpenseCategory::query()
            ->select('expense_categories.id', 'expense_categories.name', 'expense_categories.color')
            ->withSum(['expenses as total' => function ($q) use ($fromStr, $toStr) {
                $q->whereBetween('expense_date', [$fromStr, $toStr]);
            }], 'amount')
            ->orderByDesc('total')
            ->get()
            ->filter(fn ($cat) => ($cat->total ?? 0) > 0);

        /*
        |----------------------------------------------------------------------
        | Stock valuation
        |----------------------------------------------------------------------
        */
        $inventoryItems = InventoryItem::query()
            ->active()
            ->orderBy('name')
            ->get();

        $stockStats = [
            'totalValue'  => (float) $inventoryItems->sum(fn ($i) => $i->stockValue()),
            'totalItems'  => $inventoryItems->count(),
            'lowStock'    => $inventoryItems->filter(fn ($i) => $i->isLowStock())->count(),
            'outOfStock'  => $inventoryItems->filter(fn ($i) => $i->isOutOfStock())->count(),
        ];

        return view('admin.reports.index', compact(
            'fromDate', 'toDate',
            'revenue', 'orderCount', 'avgOrderValue', 'cancelledCount', 'activeCustomers',
            'deliveryCount', 'pickupCount',
            'expenseTotal', 'netProfit',
            'walletCredits', 'walletDebits', 'walletRefunds', 'walletFloat',
            'dailyPoints',
            'topItems',
            'topCustomers',
            'expenseBreakdown',
            'stockStats',
        ));
    }
}