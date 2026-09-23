<?php

namespace App\Modules\Customer\Http\Livewire;

use App\Concerns\HasRateLimiting;
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
    use HasRateLimiting;

    public const MAX_QTY_PER_ITEM = 20;

    #[Url(as: 'view', except: 'today')]
    public string $view = 'today';

    /** @var array<int, int> menuItemId => quantity */
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
    | Cart operations
    |--------------------------------------------------------------------------
    */

    public function incrementItem(int $menuItemId): void
    {
        if (! $this->canAdd($menuItemId)) {
            return;
        }

        $current = $this->cart[$menuItemId] ?? 0;

        if ($current >= self::MAX_QTY_PER_ITEM) {
            return;
        }

        $this->cart[$menuItemId] = $current + 1;
    }

    public function decrementItem(int $menuItemId): void
    {
        if (! isset($this->cart[$menuItemId])) {
            return;
        }

        $this->cart[$menuItemId]--;

        if ($this->cart[$menuItemId] <= 0) {
            unset($this->cart[$menuItemId]);
        }
    }

    public function setQuantity(int $menuItemId, mixed $value): void
    {
        if (! $this->canAdd($menuItemId)) {
            return;
        }

        $qty = (int) $value;

        if ($qty <= 0) {
            unset($this->cart[$menuItemId]);
            return;
        }

        $qty = min($qty, self::MAX_QTY_PER_ITEM);
        $this->cart[$menuItemId] = $qty;
    }

    public function removeItem(int $menuItemId): void
    {
        unset($this->cart[$menuItemId]);
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->showReviewModal = false;
        $this->orderError = null;
    }

    protected function canAdd(int $menuItemId): bool
    {
        if (! auth()->check() || ! auth()->user()->hasRole('Customer')) {
            return false;
        }

        return DailyMenu::isPublished(today(), $menuItemId);
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
            $this->rateLimit(
                key: 'place-order:'.auth()->id(),
                maxAttempts: 5,
                decaySeconds: 3600,
            );
        } catch (\Throwable $e) {
            $this->orderError = $e->getMessage();
            return null;
        }

        try {
            $order = app(OrderPlacementService::class)->place(
                customer:       auth()->user(),
                cart:           $this->cart,
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

        // Cart details — load items, attach quantity, compute totals
        $cartItems = collect();
        $cartTotal = 0.0;

        if (! empty($this->cart)) {
            $items = MenuItem::query()
                ->whereIn('id', array_keys($this->cart))
                ->get();

            $cartItems = $items->map(function (MenuItem $item) {
                $item->setAttribute('cart_quantity', $this->cart[$item->id]);
                return $item;
            });

            $cartTotal = (float) $cartItems->sum(fn ($i) => (float) $i->price * (int) $i->cart_quantity);
        }

        $cartCount = (int) array_sum($this->cart);
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
            'cartCount',
            'walletBalance',
            'walletAfter',
            'hasSufficientBalance',
        ) + ['isToday' => $this->view === 'today']);
    }
}