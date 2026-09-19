<?php

namespace App\Modules\Admin\Http\Livewire;

use App\Modules\Order\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class OrdersIndex extends Component
{
    use WithPagination;

    #[Url(as: 'date', except: '')]
    public string $dateFilter = '';

    #[Url(as: 'status', except: 'all')]
    public string $statusFilter = 'all';

    #[Url(as: 'method', except: 'all')]
    public string $methodFilter = 'all';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function mount(): void
    {
        if (! auth()->user()->can('order.view')) {
            abort(403);
        }

        if ($this->dateFilter === '') {
            $this->dateFilter = today()->toDateString();
        }
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['dateFilter', 'statusFilter', 'methodFilter', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function goToday(): void
    {
        $this->dateFilter = today()->toDateString();
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->dateFilter = today()->toDateString();
        $this->statusFilter = 'all';
        $this->methodFilter = 'all';
        $this->search = '';
        $this->resetPage();
    }

    public function layoutData(): array
    {
        return [
            'title'   => 'Orders',
            'heading' => 'Orders',
        ];
    }

    public function render(): View
    {
        $date = Carbon::parse($this->dateFilter);

        $query = Order::query()
            ->with(['user', 'items'])
            ->forDate($date);

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->methodFilter !== 'all') {
            $query->where('delivery_method', $this->methodFilter);
        }

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('order_number', 'like', $term)
                  ->orWhereHas('user', function ($uq) use ($term) {
                      $uq->where('name', 'like', $term)
                         ->orWhere('mobile', 'like', $term);
                  });
            });
        }

        $orders = $query->orderByDesc('created_at')->paginate(20);

        // Summary cards — always for the selected date, ignoring filters
        $baseForDay = Order::forDate($date);
        $counts = [
            'total'      => (clone $baseForDay)->count(),
            'active'     => (clone $baseForDay)->whereIn('status', [
                                Order::STATUS_PENDING,
                                Order::STATUS_CONFIRMED,
                                Order::STATUS_PREPARING,
                            ])->count(),
            'ready'      => (clone $baseForDay)->where('status', Order::STATUS_READY)->count(),
            'completed'  => (clone $baseForDay)->whereIn('status', [
                                Order::STATUS_DELIVERED,
                                Order::STATUS_PICKED_UP,
                            ])->count(),
            'revenue'    => (float) (clone $baseForDay)
                                ->where('status', '!=', Order::STATUS_CANCELLED)
                                ->sum('total'),
        ];

        return view('admin.orders.index', compact('orders', 'counts', 'date'));
    }
}