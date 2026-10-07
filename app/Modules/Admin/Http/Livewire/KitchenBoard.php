<?php

namespace App\Modules\Admin\Http\Livewire;

use App\Modules\Menu\Models\DailyMenu;
use App\Modules\Menu\Models\MenuItem;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Services\OrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class KitchenBoard extends Component
{
    public ?string $statusMessage = null;
    public ?string $statusType = null;

    public bool $showRejectModal = false;
    public ?int $rejectingId = null;
    public string $rejectReason = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Order::class);
    }

    public function layoutData(): array
    {
        return [
            'title'   => 'Kitchen Prep Board',
            'heading' => 'Kitchen Prep Board',
        ];
    }

    protected function flash(string $type, string $message): void
    {
        $this->statusType = $type;
        $this->statusMessage = $message;
    }

    /*
    |--------------------------------------------------------------------------
    | Sold out toggle
    |--------------------------------------------------------------------------
    */

    public function toggleSoldOut(int $menuItemId): void
    {
        if (! auth()->user()->can('menu.publish')) {
            abort(403);
        }

        // Bust the sold-out cache so the next poll reflects the change immediately
        Cache::forget('menu.sold_out.'.today()->toDateString());

        try {
            $nowSoldOut = DailyMenu::toggleSoldOut(today(), $menuItemId);

            $this->flash(
                'success',
                $nowSoldOut
                    ? 'Item marked as sold out. New orders for it will be blocked.'
                    : 'Item is available again.'
            );
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Per-order actions
    |--------------------------------------------------------------------------
    */

    public function forward(int $orderId): void
    {
        $order = Order::findOrFail($orderId);
        Gate::authorize('forward', $order);

        try {
            app(OrderService::class)->transition($order, Order::STATUS_PREPARING, auth()->user());
            $this->flash('success', $order->order_number.' → Kitchen');
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function advance(int $orderId, string $newStatus): void
    {
        $order = Order::findOrFail($orderId);
        Gate::authorize('advance', $order);

        try {
            app(OrderService::class)->transition($order, $newStatus, auth()->user());
            $this->flash('success', $order->order_number.' → '.$order->fresh()->statusLabel());
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Reject (single + bulk)
    |--------------------------------------------------------------------------
    */

    public function openReject(int $orderId): void
    {
        $order = Order::findOrFail($orderId);
        Gate::authorize('cancel', $order);

        $this->rejectingId = $orderId;
        $this->rejectReason = '';
        $this->resetErrorBag();
        $this->showRejectModal = true;
        $this->dispatch('open-modal-reject-order');
    }

    public function openBulkReject(): void
    {
        Gate::authorize('viewAny', Order::class);

        $pendingCount = Order::query()
            ->forToday()
            ->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_CONFIRMED])
            ->count();

        if ($pendingCount === 0) {
            $this->flash('error', 'No new orders to reject.');
            return;
        }

        $this->rejectingId = null;
        $this->rejectReason = '';
        $this->resetErrorBag();
        $this->showRejectModal = true;
        $this->dispatch('open-modal-reject-order');
    }

    public function closeReject(): void
    {
        $this->rejectingId = null;
        $this->rejectReason = '';
        $this->showRejectModal = false;
        $this->resetErrorBag();
        $this->dispatch('close-modal-reject-order');
    }

    public function confirmReject(): void
    {
        Gate::authorize('viewAny', Order::class);

        $this->validate([
            'rejectReason' => 'required|string|min:3|max:255',
        ], [
            'rejectReason.required' => 'A reason is required — the customer will see it.',
            'rejectReason.min'      => 'Please write a clearer reason.',
        ]);

        $service = app(OrderService::class);

        if ($this->rejectingId !== null) {
            $order = Order::findOrFail($this->rejectingId);
            Gate::authorize('cancel', $order);

            try {
                $service->cancel($order, auth()->user(), $this->rejectReason);
                $this->flash('success', $order->order_number.' rejected — customer wallet refunded.');
            } catch (\Throwable $e) {
                $this->flash('error', $e->getMessage());
            }
        } else {
            $orders = Order::query()
                ->forToday()
                ->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_CONFIRMED])
                ->get();

            $succeeded = 0;
            $failed = 0;

            foreach ($orders as $order) {
                if (! auth()->user()->can('cancel', $order)) {
                    $failed++;
                    continue;
                }

                try {
                    $service->cancel($order, auth()->user(), $this->rejectReason);
                    $succeeded++;
                } catch (\Throwable $e) {
                    $failed++;
                }
            }

            $msg = $succeeded.' order'.($succeeded === 1 ? '' : 's').' rejected — wallets refunded.';
            if ($failed > 0) {
                $msg .= ' '.$failed.' could not be rejected.';
            }

            $this->flash($failed > 0 ? 'warning' : 'success', $msg);
        }

        $this->closeReject();
    }

    public function forwardAll(): void
    {
        Gate::authorize('viewAny', Order::class);

        $orders = Order::query()
            ->forToday()
            ->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_CONFIRMED])
            ->get();

        if ($orders->isEmpty()) {
            $this->flash('error', 'No new orders to forward.');
            return;
        }

        $service = app(OrderService::class);
        $succeeded = 0;
        $failed = 0;

        foreach ($orders as $order) {
            if (! auth()->user()->can('forward', $order)) {
                $failed++;
                continue;
            }

            try {
                $service->transition($order, Order::STATUS_PREPARING, auth()->user());
                $succeeded++;
            } catch (\Throwable $e) {
                $failed++;
            }
        }

        $msg = $succeeded.' order'.($succeeded === 1 ? '' : 's').' forwarded to kitchen.';
        if ($failed > 0) {
            $msg .= ' '.$failed.' failed.';
        }

        $this->flash($failed > 0 ? 'warning' : 'success', $msg);
    }

    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */

    public function render(): View
    {
        $today = today();
        $todayStr = $today->toDateString();

        $columns = [
            [
                'key'      => 'new',
                'label'    => 'New Orders',
                'hint'     => 'Forward to kitchen or reject',
                'statuses' => [Order::STATUS_PENDING, Order::STATUS_CONFIRMED],
                'next'     => Order::STATUS_PREPARING,
            ],
            [
                'key'      => 'preparing',
                'label'    => 'Preparing',
                'hint'     => 'Mark ready when done',
                'statuses' => [Order::STATUS_PREPARING],
                'next'     => Order::STATUS_READY,
            ],
            [
                'key'      => 'ready',
                'label'    => 'Ready',
                'hint'     => 'Hand off to delivery / pickup',
                'statuses' => [Order::STATUS_READY],
                'next'     => null,
            ],
        ];

        $allStatuses = array_merge(...array_column($columns, 'statuses'));

        $orders = Order::query()
            ->with(['user', 'items'])
            ->forToday()
            ->whereIn('status', $allStatuses)
            ->orderBy('created_at')
            ->get();

        $grouped = [];
        foreach ($columns as $col) {
            $grouped[$col['key']] = $orders
                ->whereIn('status', $col['statuses'])
                ->values();
        }

        // ─── Stats: one aggregate query instead of three counts ───
        $statsRow = Order::forToday()
            ->selectRaw('
                COUNT(*) as total_today,
                SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as rejected
            ', [
                Order::STATUS_DELIVERED,
                Order::STATUS_PICKED_UP,
                Order::STATUS_CANCELLED,
            ])
            ->first();

        $stats = [
            'totalToday' => (int) $statsRow->total_today,
            'completed'  => (int) $statsRow->completed,
            'rejected'   => (int) $statsRow->rejected,
            'active'     => $orders->count(), // already in memory
        ];

        // ─── Sold-out flags for today (cached 60s) ───
$soldOutIds = Cache::remember(
    'menu.sold_out.'.$todayStr,
    60,
    fn () => DailyMenu::query()
        ->whereDate('service_date', $todayStr)
        ->whereNotNull('sold_out_at')
        ->pluck('menu_item_id')
        ->all()   // ← ensure this ->all() is present
);

        // ─── Prep summary — items still in play ───
        $prepRows = OrderItem::query()
            ->selectRaw('
                menu_item_id,
                item_name,
                item_type,
                is_veg,
                SUM(quantity) as total_qty
            ')
            ->whereHas('order', function ($q) {
                $q->forToday()
                  ->whereNotIn('status', [
                      Order::STATUS_CANCELLED,
                      Order::STATUS_DELIVERED,
                      Order::STATUS_PICKED_UP,
                  ]);
            })
            ->groupBy('menu_item_id', 'item_name', 'item_type', 'is_veg')
            ->orderByDesc('total_qty')
            ->get();

        // Menu limits cached until end of day — they rarely change mid-shift
$limits = Cache::remember(
    'menu.limits.'.$todayStr,
    86400,
    fn () => MenuItem::pluck('daily_limit', 'id')->all()
);

        $orderedTotalForLimit = OrderItem::query()
            ->selectRaw('menu_item_id, SUM(quantity) as total_qty')
            ->whereIn('menu_item_id', $prepRows->pluck('menu_item_id'))
            ->whereHas('order', function ($q) {
                $q->forToday()
                  ->where('status', '!=', Order::STATUS_CANCELLED);
            })
            ->groupBy('menu_item_id')
            ->pluck('total_qty', 'menu_item_id');

        $prepRows->each(function ($row) use ($soldOutIds, $limits, $orderedTotalForLimit) {
            $row->is_sold_out = in_array($row->menu_item_id, $soldOutIds, true);
            $row->daily_limit = $limits[$row->menu_item_id] ?? null;
            $row->ordered_total_today = (int) ($orderedTotalForLimit[$row->menu_item_id] ?? 0);
        });

        $prepMains = $prepRows->where('item_type', 'main')->values();
        $prepFastFood = $prepRows->where('item_type', 'fastfood')->values();

        return view('admin.orders.kitchen', compact(
            'columns',
            'grouped',
            'stats',
            'prepMains',
            'prepFastFood',
        ));
    }
}