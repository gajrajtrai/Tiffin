<?php

namespace App\Modules\Admin\Http\Livewire;

use App\Modules\Expense\Models\ExpenseCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class ExpenseCategoriesIndex extends Component
{
    public ?string $statusMessage = null;
    public ?string $statusType = null;

    // Edit/create modal state
    public bool $showModal = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $color = 'slate';
    public string $description = '';
    public int $sort_order = 0;
    public bool $is_active = true;

    public function mount(): void
    {
        if (! auth()->user()->can('expense.view')) {
            abort(403);
        }
    }

    public function layoutData(): array
    {
        return [
            'title'   => 'Expense Categories',
            'heading' => 'Expense Categories',
        ];
    }

    protected function flash(string $type, string $message): void
    {
        $this->statusType = $type;
        $this->statusMessage = $message;
    }

    /*
    |--------------------------------------------------------------------------
    | Modal actions
    |--------------------------------------------------------------------------
    */

    public function openCreate(): void
    {
        if (! auth()->user()->can('expense.create')) {
            abort(403);
        }

        $this->reset(['editingId', 'name', 'color', 'description', 'sort_order', 'is_active']);
        $this->color = 'slate';
        $this->sort_order = (int) ExpenseCategory::max('sort_order') + 1;
        $this->is_active = true;
        $this->resetErrorBag();
        $this->showModal = true;
        $this->dispatch('open-modal-category-form');
    }

    public function openEdit(int $id): void
    {
        if (! auth()->user()->can('expense.edit')) {
            abort(403);
        }

        $cat = ExpenseCategory::findOrFail($id);

        $this->editingId = $id;
		if ($cat->slug === 'raw-materials') {
            // Allow color/description/sort changes but preserve name & active
        }
        $this->name = $cat->name;
        $this->color = $cat->color ?: 'slate';
        $this->description = (string) $cat->description;
        $this->sort_order = (int) $cat->sort_order;
        $this->is_active = (bool) $cat->is_active;
        $this->resetErrorBag();
        $this->showModal = true;
        $this->dispatch('open-modal-category-form');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetErrorBag();
        $this->dispatch('close-modal-category-form');
    }

    public function save(): void
    {
        $this->validate([
            'name'        => 'required|string|max:100|unique:expense_categories,name'.($this->editingId ? ','.$this->editingId : ''),
            'color'       => 'required|string|in:slate,brand,success,warning,danger,info,sky,amber,emerald,rose',
            'description' => 'nullable|string|max:500',
            'sort_order'  => 'required|integer|min:0|max:9999',
            'is_active'   => 'boolean',
        ], [
            'name.unique' => 'A category with this name already exists.',
        ]);

        if ($this->editingId) {
            if (! auth()->user()->can('expense.edit')) {
                abort(403);
            }
            $cat = ExpenseCategory::findOrFail($this->editingId);
            $cat->update([
                'name'        => $this->name,
                'color'       => $this->color,
                'description' => $this->description !== '' ? $this->description : null,
                'sort_order'  => $this->sort_order,
                'is_active'   => $this->is_active,
            ]);
            $this->flash('success', 'Category updated.');
        } else {
            if (! auth()->user()->can('expense.create')) {
                abort(403);
            }
            ExpenseCategory::create([
                'name'        => $this->name,
                'slug'        => Str::slug($this->name),
                'color'       => $this->color,
                'description' => $this->description !== '' ? $this->description : null,
                'sort_order'  => $this->sort_order,
                'is_active'   => $this->is_active,
            ]);
            $this->flash('success', 'Category created.');
        }

        $this->closeModal();
    }

    public function delete(int $id): void
    {
        if (! auth()->user()->can('expense.delete')) {
            abort(403);
        }

        $cat = ExpenseCategory::findOrFail($id);

        if ($cat->slug === 'raw-materials') {
            $this->flash('error', 'Raw Materials is a system category and cannot be deleted. It is used for purchases from goods receipts.');
            return;
        }

        if ($cat->expenses()->exists()) {
            $this->flash('error', 'Cannot delete "'.$cat->name.'" — it has expense records. Set inactive instead.');
            return;
        }

        $cat->delete();
        $this->flash('success', 'Category deleted.');
    }

    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */

    public function render(): View
    {
        $categories = ExpenseCategory::query()
            ->withCount('expenses')
            ->withSum('expenses', 'amount')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.expenses.categories', compact('categories'));
    }
}