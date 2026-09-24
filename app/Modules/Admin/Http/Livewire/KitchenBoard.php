<?php

namespace App\Modules\Admin\Http\Livewire;

use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class KitchenBoard extends Component
{
    public ?string $statusMessage = null;
    public ?string $statusType = null;

    // Reject modal
    public bool $showRejectModal = false;
    public ?int $rejectingId = null;   // null + showRejectModal = bulk reject
    public string $rejectReason = '';

    public function mount(): void
    {
        if (! auth()->user()->can('order.view')) {
            abort(403);
        }
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
    | Per-order actions
    |--------------------------------------------------------------------------
    */

    public function forward(int $orderId): void
    {
        if (! auth()->user()->can('order.update-status')) {
            abort(403);
        }

        try {
            $order = Order::findOrFail($orderId);
            app(OrderService::class)->transition($order, Order::STATUS_PREPARING, auth()->user());
            $this->flash('success', $order->order_number.' → Kitchen');
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function advance(int $orderId, string $newStatus): void
    {
        if (! auth()->user()->can('order.update-status')) {
            abort(403);
        }

        try {
            $order = Order::findOrFail($orderId);
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
        if (! auth()->user()->can('order.cancel')) {
            abort(403);
        }

        $this->rejectingId = $orderId;
        $this->rejectReason = '';
        $this->resetErrorBag();
        $this->showRejectModal = true;
        $this->dispatch('open-modal-reject-order');
    }

    public function openBulkReject(): void
    {
        if (! auth()->user()->can('order.cancel')) {
            abort(403);
        }

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
        if (! auth()->user()->can('order.cancel')) {
            abort(403);
        }

        $this->validate([
            'rejectReason' => 'required|string|min:3|max:255',
        ], [
            'rejectReason.required' => 'A reason is required — the customer will see it.',
            'rejectReason.min'      => 'Please write a clearer reason.',
        ]);

        $service = app(OrderService::class);

        if ($this->rejectingId !== null) {
            // Single order
            try {
                $order = Order::findOrFail($this->rejectingId);
                $service->cancel($order, auth()->user(), $this->rejectReason);
                $this->flash('success', $order->order_number.' rejected — customer wallet refunded.');
            } catch (\Throwable $e) {
                $this->flash('error', $e->getMessage());
            }
        } else {
            // Bulk — all pending (and legacy confirmed) today
            $orders = Order::query()
                ->forToday()
                ->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_CONFIRMED])
                ->get();

            $succeeded = 0;
            $failed = 0;

            foreach ($orders as $order) {
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

    /*
    |--------------------------------------------------------------------------
    | Bulk forward
    |--------------------------------------------------------------------------
    */

    public function forwardAll(): void
    {
        if (! auth()->user()->can('order.update-status')) {
            abort(403);
        }

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
        // Two-column-model: New Orders column includes legacy CONFIRMED orders
        // so any pre-existing rows don't get stuck.
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

        $stats = [
            'totalToday' => Order::forToday()->count(),
            'completed'  => Order::forToday()
                                ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_PICKED_UP])
                                ->count(),
            'rejected'   => Order::forToday()
                                ->where('status', Order::STATUS_CANCELLED)
                                ->count(),
            'active'     => $orders->count(),
        ];

        return view('admin.orders.kitchen', compact('columns', 'grouped', 'stats'));
    }
}