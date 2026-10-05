<?php

namespace App\Modules\Customer\Http\Livewire;

use App\Modules\Menu\Models\DailyMenu;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class CustomerOrderDetail extends Component
{
    public Order $order;

    public ?string $editMessage = null;
    public ?string $editMessageType = null;

    public bool $showAddItemsModal = false;

    public function mount(Order $order): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'));
            return;
        }

        Gate::authorize('view', $order);

        $this->order = $order->load(['items', 'walletTransaction']);
    }

    protected function flash(string $type, string $message): void
    {
        $this->editMessageType = $type;
        $this->editMessage = $message;
    }

    /*
    |--------------------------------------------------------------------------
    | Add-items modal
    |--------------------------------------------------------------------------
    */

    public function openAddItems(): void
    {
        Gate::authorize('edit', $this->order);

        $this->showAddItemsModal = true;
        $this->editMessage = null;
        $this->dispatch('open-modal-add-items');
    }

    public function closeAddItems(): void
    {
        $this->showAddItemsModal = false;
        $this->dispatch('close-modal-add-items');
    }

    /*
    |--------------------------------------------------------------------------
    | Edit actions
    |--------------------------------------------------------------------------
    */

    public function addItem(int $menuItemId): void
    {
        Gate::authorize('edit', $this->order);

        try {
            $this->order = app(OrderService::class)
                ->addItem($this->order, $menuItemId, auth()->user());

            $this->flash('success', 'Item added. Your wallet has been debited.');
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function removeItem(int $orderItemId): void
    {
        Gate::authorize('edit', $this->order);

        try {
            $this->order = app(OrderService::class)
                ->removeItem($this->order, $orderItemId, auth()->user());

            $this->flash('success', 'Item removed. The amount has been refunded to your wallet.');
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function changeDeliveryMethod(string $method): void
    {
        Gate::authorize('edit', $this->order);

        try {
            $this->order = app(OrderService::class)
                ->changeDeliveryMethod($this->order, $method, auth()->user());

            $this->flash('success', 'Delivery method updated.');
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
        $availableItems = collect();

        if ($this->order->isEditable()) {
            $grouped = DailyMenu::publishedForDate(today());
            $availableItems = $grouped['main']->merge($grouped['fastfood']);
        }

        return view('public.orders.show', compact('availableItems'));
    }
}