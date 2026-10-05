<?php

namespace App\Modules\Admin\Http\Livewire;

use App\Models\User;
use App\Modules\Menu\Models\DailyMenu;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderPlacementService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class ManualOrderForm extends Component
{
    public string $customerId = '';   // '' = Walk-in
    public string $deliveryMethod = Order::METHOD_PICKUP;
    public string $paymentMethod = 'cash';
    public string $notes = '';

    /** @var array<int, string> menuItemId => quantity string */
    public array $cart = [];

    public ?string $statusMessage = null;
    public ?string $statusType = null;

    public function mount(): void
    {
        if (! auth()->user()->can('order.update-status')) {
            abort(403);
        }
    }

    public function layoutData(): array
    {
        return [
            'title'   => 'New Manual Order',
            'heading' => 'New Manual Order',
        ];
    }

    public function updatedCustomerId(): void
    {
        // If no customer selected, payment must be cash
        if ($this->customerId === '') {
            $this->paymentMethod = 'cash';
        }
    }

    public function setQuantity(int $menuItemId, mixed $value): void
    {
        $qty = (int) $value;

        if ($qty <= 0) {
            unset($this->cart[$menuItemId]);
        } else {
            $this->cart[$menuItemId] = (string) min($qty, 50);
        }
    }

    public function incrementItem(int $menuItemId): void
    {
        $current = (int) ($this->cart[$menuItemId] ?? 0);
        $this->cart[$menuItemId] = (string) min($current + 1, 50);
    }

    public function decrementItem(int $menuItemId): void
    {
        $current = (int) ($this->cart[$menuItemId] ?? 0);
        $next = $current - 1;

        if ($next <= 0) {
            unset($this->cart[$menuItemId]);
        } else {
            $this->cart[$menuItemId] = (string) $next;
        }
    }

    public function clearCart(): void
    {
        $this->cart = [];
    }

    public function submit()
    {
        $this->validate([
            'deliveryMethod' => 'required|in:delivery,pickup',
            'paymentMethod'  => 'required|in:cash,wallet',
            'notes'          => 'nullable|string|max:500',
            'cart'           => 'required|array|min:1',
        ], [
            'cart.required' => 'Add at least one item.',
            'cart.min'      => 'Add at least one item.',
        ]);

        // Convert the string-quantity cart to int-quantity cart
        $cartInts = collect($this->cart)
            ->mapWithKeys(fn ($qty, $id) => [(int) $id => (int) $qty])
            ->all();

        $customer = $this->customerId !== ''
            ? User::find($this->customerId)
            : null;

        if ($this->customerId !== '' && ! $customer) {
            $this->statusType = 'error';
            $this->statusMessage = 'Selected customer not found.';
            return null;
        }

        try {
            $order = app(OrderPlacementService::class)->placeManual(
                customer:       $customer,
                cart:           $cartInts,
                deliveryMethod: $this->deliveryMethod,
                paymentMethod:  $this->paymentMethod,
                enteredBy:      auth()->user(),
                notes:          $this->notes !== '' ? $this->notes : null,
            );

            session()->flash('status', 'Manual order '.$order->order_number.' created.');

            return $this->redirect(route('admin.orders.show', $order), navigate: true);
        } catch (\Throwable $e) {
            $this->statusType = 'error';
            $this->statusMessage = $e->getMessage();
            return null;
        }
    }

    public function render(): View
    {
        $grouped = DailyMenu::publishedForDate(today());

        $availableItems = $grouped['main']->merge($grouped['fastfood']);

        $customers = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'Customer'))
            ->orderBy('name')
            ->get(['id', 'name', 'mobile']);

        // Cart summary
        $cartItems = collect();
        $cartTotal = 0.0;

        if (! empty($this->cart)) {
            $ids = array_map('intval', array_keys($this->cart));
            $items = \App\Modules\Menu\Models\MenuItem::whereIn('id', $ids)->get();

            $cartItems = $items->map(function ($item) {
                $item->cart_qty = (int) ($this->cart[$item->id] ?? 0);
                return $item;
            });

            $cartTotal = (float) $cartItems->sum(fn ($i) => (float) $i->price * $i->cart_qty);
        }

        return view('admin.orders.manual', compact(
            'availableItems',
            'customers',
            'cartItems',
            'cartTotal',
        ));
    }
}