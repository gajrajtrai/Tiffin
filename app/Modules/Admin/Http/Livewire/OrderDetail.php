<?php

namespace App\Modules\Admin\Http\Livewire;

use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
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
        Gate::authorize('view', $order);

        $this->order = $order->load(['user', 'items', 'walletTransaction', 'enteredBy']);
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
        // Pending/Confirmed → Preparing is a "forward" (a special permission
        // path); everything else uses "advance". The two policies check
        // different state rules, so we pick the right one here.
        $policy = in_array($this->order->status, [
            Order::STATUS_PENDING,
            Order::STATUS_CONFIRMED,
        ], true) ? 'forward' : 'advance';

        Gate::authorize($policy, $this->order);

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
        Gate::authorize('cancel', $this->order);

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
    | Payment confirmation (for manual cash orders)
    |--------------------------------------------------------------------------
    */

    public function markPaid(): void
    {
        Gate::authorize('view', $this->order);

        if ($this->order->payment_status === 'paid') {
            $this->flash('error', 'Order is already marked paid.');
            return;
        }

        $this->order->payment_status = 'paid';
        $this->order->save();
        $this->order->refresh();

        $this->flash('success', 'Payment recorded.');
    }

    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */

    public function render(): View
    {
        $nextActions = match ($this->order->status) {
            Order::STATUS_PENDING,
            Order::STATUS_CONFIRMED => [['status' => Order::STATUS_PREPARING, 'label' => 'Forward to Kitchen', 'variant' => 'brand']],
            Order::STATUS_PREPARING => [['status' => Order::STATUS_READY,     'label' => 'Mark Ready',      'variant' => 'success']],
            Order::STATUS_READY     => $this->order->isDelivery()
                                        ? [['status' => Order::STATUS_DELIVERED, 'label' => 'Mark Delivered', 'variant' => 'success']]
                                        : [['status' => Order::STATUS_PICKED_UP, 'label' => 'Mark Picked Up', 'variant' => 'success']],
            default                 => [],
        };

        return view('admin.orders.show', compact('nextActions'));
    }
}