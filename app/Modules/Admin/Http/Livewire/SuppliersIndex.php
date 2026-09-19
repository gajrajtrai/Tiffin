<?php

namespace App\Modules\Admin\Http\Livewire;

use App\Modules\Supplier\Models\Supplier;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class SuppliersIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'active', except: '1')]
    public string $activeOnly = '1';

    public ?string $statusMessage = null;
    public ?string $statusType = null;

    public function mount(): void
    {
        if (! auth()->user()->can('supplier.view')) {
            abort(403);
        }
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'activeOnly'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->activeOnly = '1';
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        if (! auth()->user()->can('supplier.edit')) {
            abort(403);
        }

        $supplier = Supplier::findOrFail($id);
        $supplier->is_active = ! $supplier->is_active;
        $supplier->save();

        $this->statusType = 'success';
        $this->statusMessage = $supplier->name.' is now '.($supplier->is_active ? 'active' : 'inactive').'.';
    }

    public function delete(int $id): void
    {
        if (! auth()->user()->can('supplier.delete')) {
            abort(403);
        }

        $supplier = Supplier::findOrFail($id);

        if ($supplier->purchaseOrders()->exists()) {
            $this->statusType = 'error';
            $this->statusMessage = 'Cannot delete "'.$supplier->name.'" — it has purchase order history. Set inactive instead.';
            return;
        }

        $supplier->delete();
        $this->statusType = 'success';
        $this->statusMessage = 'Supplier deleted.';
    }

    public function layoutData(): array
    {
        return [
            'title'   => 'Suppliers',
            'heading' => 'Suppliers',
        ];
    }

    public function render(): View
    {
        $query = Supplier::query();

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('contact_person', 'like', $term)
                  ->orWhere('mobile', 'like', $term)
                  ->orWhere('supplies', 'like', $term);
            });
        }

        if ($this->activeOnly === '1') {
            $query->active();
        }

        $suppliers = $query->ordered()->paginate(20);

        $counts = [
            'total'    => Supplier::count(),
            'active'   => Supplier::active()->count(),
            'inactive' => Supplier::where('is_active', false)->count(),
        ];

        return view('admin.suppliers.index', compact('suppliers', 'counts'));
    }
}