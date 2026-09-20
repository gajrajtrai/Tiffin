<?php

namespace App\Modules\Customer\Http\Livewire;

use App\Modules\Order\Models\Order;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.public')]
class CustomerOrdersIndex extends Component
{
    use WithPagination;

    public function mount(): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'));
        }
    }

    public function render(): View
    {
        $user = auth()->user();

        $orders = Order::query()
            ->where('user_id', $user->id)
            ->with('items')
            ->orderByDesc('service_date')
            ->orderByDesc('id')
            ->paginate(15);

        return view('public.orders.index', compact('user', 'orders'));
    }
}