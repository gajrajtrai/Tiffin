<?php

namespace App\Modules\Admin\Http\Livewire;

use App\Modules\Expense\Models\Expense;
use App\Modules\Expense\Models\ExpenseCategory;
use App\Modules\Supplier\Models\Supplier;
use Illuminate\Support\Facades\Gate;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.admin')]
class ExpenseForm extends Component
{
    use WithFileUploads;

    public ?Expense $expense = null;

    public string $expense_category_id = '';
    public string $supplier_id = '';
    public string $expense_date = '';
    public string $description = '';
    public string $amount = '';
    public string $payment_method = Expense::METHOD_CASH;
    public string $payment_reference = '';
    public string $status = Expense::STATUS_PAID;
    public string $notes = '';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $receipt = null;
    public bool $remove_receipt = false;

    public function mount(?Expense $expense = null): void
    {
        if ($expense && $expense->exists) {
            Gate::authorize('update', $expense);

            $this->expense = $expense;
            $this->expense_category_id = (string) $expense->expense_category_id;
            $this->supplier_id = (string) ($expense->supplier_id ?? '');
            $this->expense_date = $expense->expense_date?->toDateString() ?? today()->toDateString();
            $this->description = $expense->description;
            $this->amount = (string) $expense->amount;
            $this->payment_method = $expense->payment_method;
            $this->payment_reference = (string) $expense->payment_reference;
            $this->status = $expense->status;
            $this->notes = (string) $expense->notes;
        } else {
            Gate::authorize('create', Expense::class);

            $this->expense_date = today()->toDateString();
        }
    }

    public function layoutData(): array
    {
        return [
            'title'   => $this->expense ? 'Edit Expense' : 'New Expense',
            'heading' => $this->expense ? 'Edit Expense' : 'Create Expense',
        ];
    }

    protected function rules(): array
    {
        return [
            'expense_category_id' => 'required|exists:expense_categories,id',
            'supplier_id'         => 'nullable|exists:suppliers,id',
            'expense_date'        => 'required|date|before_or_equal:today',
            'description'         => 'required|string|max:255',
            'amount'              => 'required|numeric|min:0.01|max:1000000',
            'payment_method'      => 'required|in:cash,bank_transfer,cheque,other',
            'payment_reference'   => 'nullable|string|max:100',
            'status'              => 'required|in:draft,approved,paid',
            'notes'               => 'nullable|string|max:1000',
            'receipt'             => 'nullable|file|max:5120|mimes:jpeg,png,webp,pdf',
        ];
    }

    protected function messages(): array
    {
        return [
            'expense_category_id.required' => 'Choose a category.',
            'expense_date.before_or_equal' => 'Expense date cannot be in the future.',
            'amount.min'                   => 'Amount must be greater than zero.',
            'receipt.mimes'                => 'Receipt must be a JPEG, PNG, WebP image, or PDF.',
            'receipt.max'                  => 'Receipt must be smaller than 5 MB.',
        ];
    }

    public function save()
    {
        $this->validate();

        $data = [
            'expense_category_id' => $this->expense_category_id,
            'supplier_id'         => $this->supplier_id !== '' ? $this->supplier_id : null,
            'expense_date'        => $this->expense_date,
            'description'         => $this->description,
            'amount'              => $this->amount,
            'payment_method'      => $this->payment_method,
            'payment_reference'   => $this->payment_reference !== '' ? $this->payment_reference : null,
            'status'              => $this->status,
            'notes'               => $this->notes !== '' ? $this->notes : null,
        ];

        if ($this->expense) {
            $this->expense->update($data);
            $expense = $this->expense;
            $flash = 'Expense updated.';
        } else {
            $data['recorded_by'] = auth()->id();
            $expense = Expense::create($data);
            $flash = 'Expense recorded.';
        }

        // Handle receipt media
        if ($this->remove_receipt) {
            $expense->clearMediaCollection(Expense::MEDIA_RECEIPT);
        }

        if ($this->receipt) {
            $expense->clearMediaCollection(Expense::MEDIA_RECEIPT);
            $expense->addMedia($this->receipt)
                    ->usingFileName('receipt_'.$expense->id.'_'.time().'.'.$this->receipt->getClientOriginalExtension())
                    ->toMediaCollection(Expense::MEDIA_RECEIPT);
        }

        session()->flash('status', $flash);

        return $this->redirect(route('admin.expenses.index'), navigate: true);
    }

    public function render(): View
    {
        $categories = ExpenseCategory::active()->ordered()->get(['id', 'name']);
        $suppliers = Supplier::active()->ordered()->get(['id', 'name']);

        return view('admin.expenses.form', compact('categories', 'suppliers'));
    }
}