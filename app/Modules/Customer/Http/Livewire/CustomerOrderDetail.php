<?php

namespace App\Modules\Customer\Http\Livewire;

use App\Modules\Order\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.public')]
class CustomerOrderDetail extends Component
{
    public Order $order;

    public function mount(Order $order): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'));
            return;
        }

        // Policy: customers see their own orders; staff with order.view see all
        Gate::authorize('view', $order);

        $this->order = $order->load(['items', 'walletTransaction']);
    }

    public function render(): View
    {
        return view('public.orders.show');
    }
}