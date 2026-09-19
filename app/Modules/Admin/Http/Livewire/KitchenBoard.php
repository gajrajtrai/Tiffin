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

    /**
     * Advance an order to its next status.
     */
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

    public function render(): View
    {
        $columns = [
            [
                'status' => Order::STATUS_PENDING,
                'label'  => 'New Orders',
                'hint'   => 'Confirm to accept',
            ],
            [
                'status' => Order::STATUS_CONFIRMED,
                'label'  => 'Confirmed',
                'hint'   => 'Start preparing when ready',
            ],
            [
                'status' => Order::STATUS_PREPARING,
                'label'  => 'Preparing',
                'hint'   => 'Mark ready when done',
            ],
            [
                'status' => Order::STATUS_READY,
                'label'  => 'Ready',
                'hint'   => 'Hand off to delivery/pickup',
            ],
        ];

        $orders = Order::query()
            ->with(['user', 'items'])
            ->forToday()
            ->whereIn('status', array_column($columns, 'status'))
            ->orderBy('created_at')
            ->get();

        $grouped = [];
        foreach ($columns as $col) {
            $grouped[$col['status']] = $orders->where('status', $col['status'])->values();
        }

        $completed = Order::query()
            ->with(['user', 'items'])
            ->forToday()
            ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_PICKED_UP])
            ->count();

        $stats = [
            'totalToday' => Order::forToday()->count(),
            'completed'  => $completed,
            'active'     => $orders->count(),
        ];

        return view('admin.orders.kitchen', compact('columns', 'grouped', 'stats'));
    }
}