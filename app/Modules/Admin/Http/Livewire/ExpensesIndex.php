<?php

namespace App\Modules\Admin\Http\Livewire;

use App\Modules\Expense\Models\Expense;
use App\Modules\Expense\Models\ExpenseCategory;
use App\Modules\Supplier\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class ExpensesIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'cat', except: '')]
    public string $categoryFilter = '';

    #[Url(as: 'show', except: 'active')]
    public string $showFilter = 'active';

    #[Url(as: 'from', except: '')]
    public string $from = '';

    #[Url(as: 'to', except: '')]
    public string $to = '';

    public ?string $statusMessage = null;
    public ?string $statusType = null;

    public ?int $voidingId = null;
    public string $voidReason = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Expense::class);

        if ($this->from === '') {
            $this->from = now()->startOfMonth()->toDateString();
        }
        if ($this->to === '') {
            $this->to = today()->toDateString();
        }
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'categoryFilter', 'showFilter', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function presetToday(): void
    {
        $this->from = today()->toDateString();
        $this->to = today()->toDateString();
        $this->resetPage();
    }

    public function presetThisMonth(): void
    {
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = today()->toDateString();
        $this->resetPage();
    }

    public function presetLastMonth(): void
    {
        $this->from = now()->subMonth()->startOfMonth()->toDateString();
        $this->to = now()->subMonth()->endOfMonth()->toDateString();
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->categoryFilter = '';
        $this->showFilter = 'active';
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = today()->toDateString();
        $this->resetPage();
    }

    /*
    |--------------------------------------------------------------------------
    | Void
    |--------------------------------------------------------------------------
    */

    public function openVoid(int $id): void
    {
        $expense = Expense::findOrFail($id);

        if (! auth()->user()->can('void', $expense)) {
            if ($expense->isGrLinked()) {
                $this->statusType = 'error';
                $this->statusMessage = 'GR-linked expenses are managed from the goods receipt and cannot be voided here.';
                return;
            }

            if ($expense->isVoided()) {
                $this->statusType = 'error';
                $this->statusMessage = 'This expense is already voided.';
                return;
            }

            abort(403);
        }

        $this->voidingId = $id;
        $this->voidReason = '';
        $this->resetErrorBag();
        $this->dispatch('open-modal-void-expense');
    }

    public function closeVoid(): void
    {
        $this->voidingId = null;
        $this->voidReason = '';
        $this->resetErrorBag();
        $this->dispatch('close-modal-void-expense');
    }

    public function voidExpense(): void
    {
        $this->validate([
            'voidReason' => 'required|string|min:3|max:255',
        ], [
            'voidReason.required' => 'A reason is required for the audit trail.',
            'voidReason.min'      => 'Please give a clearer reason.',
        ]);

        $expense = Expense::findOrFail($this->voidingId);
        Gate::authorize('void', $expense);

        $expense->voided_at = now();
        $expense->voided_by = auth()->id();
        $expense->void_reason = $this->voidReason;
        $expense->save();

        $this->closeVoid();

        $this->statusType = 'success';
        $this->statusMessage = 'Expense '.$expense->expense_number.' voided.';
    }

    public function deleteDraft(int $id): void
    {
        $expense = Expense::findOrFail($id);
        Gate::authorize('delete', $expense);

        $expense->clearMediaCollection(Expense::MEDIA_RECEIPT);
        $expense->delete();

        $this->statusType = 'success';
        $this->statusMessage = 'Draft expense deleted.';
    }

    public function layoutData(): array
    {
        return [
            'title'   => 'Expenses',
            'heading' => 'Expenses',
        ];
    }

    public function render(): View
    {
        $fromDate = Carbon::parse($this->from)->startOfDay();
        $toDate = Carbon::parse($this->to)->endOfDay();
        $fromStr = $fromDate->toDateString();
        $toStr = $toDate->toDateString();

        $query = Expense::query()
            ->with(['category', 'supplier', 'goodsReceipt'])
            ->whereBetween('expense_date', [$fromStr, $toStr]);

        if ($this->showFilter === 'active') {
            $query->active();
        } elseif ($this->showFilter === 'voided') {
            $query->voided();
        }

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('expense_number', 'like', $term)
                  ->orWhere('description', 'like', $term)
                  ->orWhere('payment_reference', 'like', $term);
            });
        }

        if ($this->categoryFilter !== '') {
            $query->where('expense_category_id', $this->categoryFilter);
        }

        $expenses = $query->orderByDesc('expense_date')->orderByDesc('id')->paginate(25);

        $rangeQuery = Expense::query()
            ->whereBetween('expense_date', [$fromStr, $toStr])
            ->active();

        $stats = [
            'total'          => (float) (clone $rangeQuery)->sum('amount'),
            'count'          => (clone $rangeQuery)->count(),
            'categoriesUsed' => (clone $rangeQuery)->distinct('expense_category_id')->count('expense_category_id'),
            'withoutReceipt' => (clone $rangeQuery)->whereDoesntHave('media', function ($q) {
                                    $q->where('collection_name', Expense::MEDIA_RECEIPT);
                                })->count(),
            'voidedCount'    => Expense::query()
                                    ->whereBetween('expense_date', [$fromStr, $toStr])
                                    ->voided()
                                    ->count(),
        ];

        $categoryBreakdown = ExpenseCategory::query()
            ->select('expense_categories.id', 'expense_categories.name', 'expense_categories.color')
            ->withSum(['expenses as total' => function ($q) use ($fromStr, $toStr) {
                $q->whereBetween('expense_date', [$fromStr, $toStr])
                  ->whereNull('voided_at');
            }], 'amount')
            ->orderByDesc('total')
            ->get()
            ->filter(fn ($c) => ($c->total ?? 0) > 0);

        $categories = ExpenseCategory::active()->ordered()->get(['id', 'name']);
        $suppliers = Supplier::active()->ordered()->get(['id', 'name']);

        return view('admin.expenses.index', compact(
            'expenses',
            'stats',
            'categoryBreakdown',
            'categories',
            'suppliers',
            'fromDate',
            'toDate',
        ));
    }
}