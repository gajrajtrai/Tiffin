<?php

namespace App\Modules\Customer\Http\Livewire;

use App\Modules\Order\Models\Order;
use Illuminate\Contracts\View\View;
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

        // Only the owner can view — staff view this from the admin panel
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        $this->order = $order->load(['items', 'walletTransaction']);
    }

    public function render(): View
    {
        return view('public.orders.show');
    }
}