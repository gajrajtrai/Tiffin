<?php

namespace App\Modules\Customer\Http\Livewire;

use App\Modules\Menu\Models\DailyMenu;
use App\Modules\Menu\Models\MenuItem;
use App\Modules\Menu\Models\ServiceDay;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderPlacementService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.public')]
class MenuBrowse extends Component
{
    #[Url(as: 'view', except: 'today')]
    public string $view = 'today';

    /** @var array<int> selected menu item IDs */
    public array $cart = [];

    public string $deliveryMethod = Order::METHOD_DELIVERY;
    public bool $showReviewModal = false;
    public ?string $orderError = null;

    public function setView(string $view): void
    {
        if (in_array($view, ['today', 'all'], true)) {
            $this->view = $view;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Cart
    |--------------------------------------------------------------------------
    */

    public function toggleItem(int $menuItemId): void
    {
        if (! auth()->check() || ! auth()->user()->hasRole('Customer')) {
            return;
        }

        if (! DailyMenu::isPublished(today(), $menuItemId)) {
            return;
        }

        $idx = array_search($menuItemId, $this->cart, true);
        if ($idx !== false) {
            unset($this->cart[$idx]);
            $this->cart = array_values($this->cart);
        } else {
            $this->cart[] = $menuItemId;
        }
    }

    public function removeItem(int $menuItemId): void
    {
        $this->cart = array_values(array_filter($this->cart, fn ($id) => $id !== $menuItemId));
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->showReviewModal = false;
        $this->orderError = null;
    }

    /*
    |--------------------------------------------------------------------------
    | Review + confirm
    |--------------------------------------------------------------------------
    */

    public function openReview(): void
    {
        if (empty($this->cart)) {
            $this->orderError = 'Your selection is empty.';
            return;
        }

        $this->orderError = null;
        $this->showReviewModal = true;
        $this->dispatch('open-modal-review-order');
    }

    public function closeReview(): void
    {
        $this->showReviewModal = false;
        $this->orderError = null;
        $this->dispatch('close-modal-review-order');
    }

    public function confirm()
    {
        if (! auth()->check() || ! auth()->user()->hasRole('Customer')) {
            $this->orderError = 'Please sign in as a customer to place an order.';
            return null;
        }

        try {
            $order = app(OrderPlacementService::class)->place(
                customer:       auth()->user(),
                menuItemIds:    $this->cart,
                deliveryMethod: $this->deliveryMethod,
            );
        } catch (\Throwable $e) {
            $this->orderError = $e->getMessage();
            return null;
        }

        $this->cart = [];
        $this->showReviewModal = false;

        session()->flash('order_just_placed', true);

        return $this->redirect(route('orders.show', $order), navigate: true);
    }

    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */

    public function render(): View
    {
        $today = today();
        $serviceDay = ServiceDay::forDate($today);

        if ($this->view === 'today') {
            $grouped = DailyMenu::publishedForDate($today);
            $mains = $grouped['main'];
            $fastFood = $grouped['fastfood'];
            $publishedIds = $mains->pluck('id')->merge($fastFood->pluck('id'))->all();
        } else {
            $mains = MenuItem::active()->mains()->ordered()->get();
            $fastFood = MenuItem::active()->fastFood()->ordered()->get();
            $publishedIds = DailyMenu::query()
                ->whereDate('service_date', $today)
                ->pluck('menu_item_id')
                ->all();
        }

        $cartItems = collect();
        $cartTotal = 0.0;

        if (! empty($this->cart)) {
            $cartItems = MenuItem::query()->whereIn('id', $this->cart)->get();
            $cartTotal = (float) $cartItems->sum('price');
        }

        $walletBalance = (float) (auth()->user()?->wallet_balance ?? 0);
        $walletAfter = $walletBalance - $cartTotal;
        $hasSufficientBalance = $walletAfter >= 0;

        return view('public.menu', compact(
            'mains',
            'fastFood',
            'serviceDay',
            'publishedIds',
            'cartItems',
            'cartTotal',
            'walletBalance',
            'walletAfter',
            'hasSufficientBalance',
        ) + ['isToday' => $this->view === 'today']);
    }
}