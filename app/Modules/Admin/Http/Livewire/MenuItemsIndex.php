<?php

namespace App\Modules\Admin\Http\Livewire;

use App\Modules\Menu\Models\MenuItem;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class MenuItemsIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'type', except: '')]
    public string $typeFilter = '';

    #[Url(as: 'diet', except: '')]
    public string $dietFilter = '';

    public ?string $statusMessage = null;
    public ?string $statusType = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', MenuItem::class);
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'typeFilter', 'dietFilter'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->typeFilter = '';
        $this->dietFilter = '';
        $this->resetPage();
    }

    public function toggleActive(int $itemId): void
    {
        $item = MenuItem::findOrFail($itemId);
        Gate::authorize('update', $item);

        $item->is_active = ! $item->is_active;
        $item->save();

        $this->statusType = 'success';
        $this->statusMessage = $item->name.' is now '.($item->is_active ? 'active' : 'inactive').'.';
    }

    public function delete(int $itemId): void
    {
        $item = MenuItem::findOrFail($itemId);
        Gate::authorize('delete', $item);

        if (! $item->canBeDeleted()) {
            $this->statusType = 'error';
            $this->statusMessage = 'Cannot delete "'.$item->name.'" — it appears in order history. Set it inactive instead.';
            return;
        }

        $item->delete();

        $this->statusType = 'success';
        $this->statusMessage = 'Menu item deleted.';
    }

    public function layoutData(): array
    {
        return [
            'title'   => 'Menu',
            'heading' => 'Menu Items',
        ];
    }

    public function render(): View
    {
        $query = MenuItem::query();

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where('name', 'like', $term);
        }

        if ($this->typeFilter !== '') {
            $query->where('type', $this->typeFilter);
        }

        if ($this->dietFilter === 'veg') {
            $query->where('is_veg', true);
        } elseif ($this->dietFilter === 'nonveg') {
            $query->where('is_veg', false);
        }

        $items = $query->ordered()->paginate(15);

        $counts = [
            'total'    => MenuItem::count(),
            'mains'    => MenuItem::mains()->count(),
            'fastfood' => MenuItem::fastFood()->count(),
            'active'   => MenuItem::active()->count(),
        ];

        return view('admin.menu.index', compact('items', 'counts'));
    }
}