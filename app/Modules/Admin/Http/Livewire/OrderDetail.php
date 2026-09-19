<?php

namespace App\Modules\Admin\Http\Livewire;

use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class OrderDetail extends Component
{
    public Order $order;

    public ?string $statusMessage = null;
    public ?string $statusType = null;

    public string $cancelReason = '';

    public function mount(Order $order): void
    {
        if (! auth()->user()->can('order.view')) {
            abort(403);
        }

        $this->order = $order->load(['user', 'items', 'walletTransaction']);
    }

    public function layoutData(): array
    {
        return [
            'title'   => 'Order '.$this->order->order_number,
            'heading' => 'Order Details',
        ];
    }

    protected function flash(string $type, string $message): void
    {
        $this->statusType = $type;
        $this->statusMessage = $message;
    }

    /*
    |--------------------------------------------------------------------------
    | Status transitions
    |--------------------------------------------------------------------------
    */

	public function advance(string $newStatus): void
    {
        if (! auth()->user()->can('order.update-status')) {
            abort(403);
        }

        try {
            $this->order = app(OrderService::class)
                ->transition($this->order, $newStatus, auth()->user());

            $this->flash('success', 'Order moved to '.$this->order->statusLabel().'.');
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function cancel(): void
    {
        if (! auth()->user()->can('order.cancel')) {
            abort(403);
        }

        $this->validate([
            'cancelReason' => 'required|string|min:3|max:255',
        ], [
            'cancelReason.required' => 'A reason is required.',
            'cancelReason.min'      => 'Please give at least a short reason.',
        ]);

        try {
            $this->order = app(OrderService::class)
                ->cancel($this->order, auth()->user(), $this->cancelReason);

            $this->reset('cancelReason');
            $this->dispatch('close-modal-cancel-order');
            $this->flash('success', 'Order cancelled. Customer wallet refunded.');
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */

    public function render(): View
    {
        $nextActions = match ($this->order->status) {
            Order::STATUS_PENDING   => [['status' => Order::STATUS_CONFIRMED, 'label' => 'Confirm order',  'variant' => 'brand']],
            Order::STATUS_CONFIRMED => [['status' => Order::STATUS_PREPARING, 'label' => 'Start preparing', 'variant' => 'brand']],
            Order::STATUS_PREPARING => [['status' => Order::STATUS_READY,     'label' => 'Mark ready',      'variant' => 'success']],
            Order::STATUS_READY     => $this->order->isDelivery()
                                        ? [['status' => Order::STATUS_DELIVERED, 'label' => 'Mark delivered', 'variant' => 'success']]
                                        : [['status' => Order::STATUS_PICKED_UP, 'label' => 'Mark picked up', 'variant' => 'success']],
            default                 => [],
        };

        return view('admin.orders.show', compact('nextActions'));
    }
}