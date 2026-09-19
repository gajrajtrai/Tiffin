<?php

namespace App\Modules\Admin\Http\Livewire;

use App\Modules\Supplier\Models\GoodsReceipt;
use App\Modules\Supplier\Models\Supplier;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class GoodsReceiptsIndex extends Component
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
            'title'   => 'Goods Receipts',
            'heading' => 'Goods Receipts',
        ];
    }

    public function render(): View
    {
        $query = GoodsReceipt::query()->with(['supplier', 'purchaseOrder', 'items']);

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('receipt_number', 'like', $term)
                  ->orWhereHas('purchaseOrder', fn ($pq) => $pq->where('po_number', 'like', $term))
                  ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', $term));
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->supplierFilter !== '') {
            $query->where('supplier_id', $this->supplierFilter);
        }

        $receipts = $query->orderByDesc('received_date')->orderByDesc('id')->paginate(20);

        $counts = [
            'total'     => GoodsReceipt::count(),
            'draft'     => GoodsReceipt::draft()->count(),
            'confirmed' => GoodsReceipt::confirmed()->count(),
        ];

        $suppliers = Supplier::active()->ordered()->get(['id', 'name']);

        return view('admin.receipts.index', compact('receipts', 'counts', 'suppliers'));
    }
}