<?php

namespace App\Modules\Admin\Http\Livewire;

use App\Modules\Supplier\Models\PurchaseOrder;
use App\Modules\Supplier\Models\Supplier;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class PurchaseOrdersIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'status', except: 'all')]
    public string $statusFilter = 'all';

    #[Url(as: 'supplier', except: '')]
    public string $supplierFilter = '';

    public function mount(): void
    {
        if (! auth()->user()->can('purchase.view')) {
            abort(403);
        }
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'statusFilter', 'supplierFilter'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->supplierFilter = '';
        $this->resetPage();
    }

    public function layoutData(): array
    {
        return [
            'title'   => 'Purchase Orders',
            'heading' => 'Purchase Orders',
        ];
    }

    public function render(): View
    {
        $query = PurchaseOrder::query()->with(['supplier', 'items']);

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('po_number', 'like', $term)
                  ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', $term));
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->supplierFilter !== '') {
            $query->where('supplier_id', $this->supplierFilter);
        }

        $orders = $query->orderByDesc('order_date')->orderByDesc('id')->paginate(20);

        $counts = [
            'total'       => PurchaseOrder::count(),
            'draft'       => PurchaseOrder::where('status', PurchaseOrder::STATUS_DRAFT)->count(),
            'open'        => PurchaseOrder::open()->count(),
            'received'    => PurchaseOrder::where('status', PurchaseOrder::STATUS_RECEIVED)->count(),
        ];

        $suppliers = Supplier::active()->ordered()->get(['id', 'name']);

        return view('admin.purchases.index', compact('orders', 'counts', 'suppliers'));
    }
}