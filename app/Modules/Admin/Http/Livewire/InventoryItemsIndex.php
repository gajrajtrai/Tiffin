<?php

namespace App\Modules\Admin\Http\Livewire;

use App\Modules\Inventory\Models\InventoryItem;
use Illuminate\Support\Facades\Gate;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class InventoryItemsIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'cat', except: '')]
    public string $categoryFilter = '';

    #[Url(as: 'low', except: '0')]
    public string $lowOnly = '0';

    public function mount(): void
    {
        Gate::authorize('viewAny', \App\Modules\Inventory\Models\InventoryItem::class);
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'categoryFilter', 'lowOnly'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->categoryFilter = '';
        $this->lowOnly = '0';
        $this->resetPage();
    }

    public function toggleLowOnly(): void
    {
        $this->lowOnly = $this->lowOnly === '1' ? '0' : '1';
        $this->resetPage();
    }

    public function layoutData(): array
    {
        return [
            'title'   => 'Inventory',
            'heading' => 'Inventory',
        ];
    }

    public function render(): View
    {
        $query = InventoryItem::query();

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('sku', 'like', $term);
            });
        }

        if ($this->categoryFilter !== '') {
            $query->category($this->categoryFilter);
        }

        if ($this->lowOnly === '1') {
            $query->lowStock();
        }

        $items = $query->ordered()->paginate(20);

        $allItems = InventoryItem::all();

        $counts = [
            'total'       => $allItems->count(),
            'lowStock'    => $allItems->filter(fn ($i) => $i->isLowStock())->count(),
            'outOfStock'  => $allItems->filter(fn ($i) => $i->isOutOfStock())->count(),
            'totalValue'  => (float) $allItems->sum(fn ($i) => $i->stockValue()),
        ];

        $categories = InventoryItem::query()
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('admin.inventory.index', compact('items', 'counts', 'categories'));
    }
}