<?php

namespace App\Modules\Admin\Http\Livewire;

use App\Modules\Supplier\Models\GoodsReceipt;
use App\Modules\Supplier\Services\GoodsReceiptService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class GoodsReceiptDetail extends Component
{
    public GoodsReceipt $gr;

    public ?string $statusMessage = null;
    public ?string $statusType = null;

    public string $paymentMethod = 'cash';
    public string $paymentReference = '';
    /**
     * Line editor state, keyed by GoodsReceiptItem id.
     * @var array<int, array{quantity_received: mixed, unit_cost: mixed, batch_code: string, expiry_date: string, notes: string}>
     */
    public array $lines = [];

    public function mount(GoodsReceipt $gr): void
    {
        Gate::authorize('view', $gr);

        $this->gr = $gr->load(['items.inventoryItem', 'purchaseOrder', 'supplier', 'receivedBy', 'expense']);
        $this->loadLines();

        $this->paymentMethod = (string) ($this->gr->payment_method ?: 'cash');
        $this->paymentReference = (string) ($this->gr->payment_reference ?? '');
    }

    public function layoutData(): array
    {
        return [
            'title'   => 'GR '.$this->gr->receipt_number,
            'heading' => 'Goods Receipt',
        ];
    }

    protected function flash(string $type, string $message): void
    {
        $this->statusType = $type;
        $this->statusMessage = $message;
    }

    protected function loadLines(): void
    {
        $this->lines = [];

        foreach ($this->gr->items as $item) {
            $this->lines[$item->id] = [
                'quantity_received' => (float) $item->quantity_received,
                'unit_cost'         => (float) $item->unit_cost,
                'batch_code'        => (string) $item->batch_code,
                'expiry_date'       => $item->expiry_date?->toDateString() ?? '',
                'notes'             => (string) $item->notes,
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Line editing
    |--------------------------------------------------------------------------
    */

    /**
     * Update one line item based on the current $lines state.
     */
    protected function persistLine(int $itemId): void
    {
        $item = $this->gr->items->firstWhere('id', $itemId);

        if (! $item || ! isset($this->lines[$itemId])) {
            return;
        }

        $line = $this->lines[$itemId];
        $qty = (float) $line['quantity_received'];
        $cost = (float) $line['unit_cost'];

        $item->update([
            'quantity_received' => $qty,
            'unit_cost'         => $cost,
            'line_total'        => $qty * $cost,
            'batch_code'        => $line['batch_code'] !== '' ? $line['batch_code'] : null,
            'expiry_date'       => $line['expiry_date'] !== '' ? $line['expiry_date'] : null,
            'notes'             => $line['notes'] !== '' ? $line['notes'] : null,
        ]);
    }

    /**
     * Called by the "Save changes" button. Persists every line and updates the header totals.
     */
    public function saveChanges(): void
    {
        if (! auth()->user()->can('update', $this->gr)) {
            if (! $this->gr->isDraft()) {
                $this->flash('error', 'Only draft receipts can be edited.');
                return;
            }
            abort(403);
        }

        DB::transaction(function () {
            foreach ($this->gr->items as $item) {
                $this->persistLine($item->id);
            }
        });

        $this->gr->refresh();
        $this->gr->recalculateTotals();
        $this->gr = $this->gr->fresh(['items.inventoryItem', 'purchaseOrder', 'supplier']);
        $this->loadLines();

        $this->flash('success', 'Draft updated.');
    }

    /**
     * Remove a line — for when a delivery is short of a full item.
     */
    public function removeLine(int $itemId): void
    {
        if (! auth()->user()->can('update', $this->gr)) {
            if (! $this->gr->isDraft()) {
                $this->flash('error', 'Only draft receipts can be edited.');
                return;
            }
            abort(403);
        }

        $item = $this->gr->items->firstWhere('id', $itemId);

        if (! $item) {
            return;
        }

        $item->delete();
        unset($this->lines[$itemId]);

        $this->gr->refresh();
        $this->gr->recalculateTotals();
        $this->gr = $this->gr->fresh(['items.inventoryItem', 'purchaseOrder', 'supplier']);

        $this->flash('success', 'Line removed.');
    }

    /*
    |--------------------------------------------------------------------------
    | Confirm
    |--------------------------------------------------------------------------
    */

    public function confirm(): void
    {
        if (! auth()->user()->can('confirm', $this->gr)) {
            if (! $this->gr->isDraft()) {
                $this->flash('error', 'Only draft receipts can be confirmed.');
                return;
            }
            abort(403);
        }

        // Persist any pending line edits first
        DB::transaction(function () {
            foreach ($this->gr->items as $item) {
                $this->persistLine($item->id);
            }
        });

        $this->gr->refresh();

        try {
            $this->gr = app(GoodsReceiptService::class)
                ->confirm($this->gr, auth()->user());

            $this->gr = $this->gr->fresh(['items.inventoryItem', 'purchaseOrder', 'supplier', 'receivedBy']);
            $this->loadLines();

            $this->flash(
                'success',
                'Receipt confirmed. Stock has been added and the PO updated.'
            );
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
        }
    }

    public function deleteReceipt()
    {
        if (! auth()->user()->can('delete', $this->gr)) {
            if (! $this->gr->isDraft()) {
                $this->flash('error', 'Confirmed receipts cannot be deleted.');
                return null;
            }
            abort(403);
        }

        $this->gr->items()->delete();
        $this->gr->delete();

        session()->flash('status', 'Draft receipt deleted.');

        return $this->redirect(route('admin.receipts.index'), navigate: true);
    }

    /*
    |--------------------------------------------------------------------------
    | Render
    |--------------------------------------------------------------------------
    */
    public function updatedPaymentMethod(): void
    {
        $this->persistPaymentMethod();
    }

    public function updatedPaymentReference(): void
    {
        $this->persistPaymentMethod();
    }

    protected function persistPaymentMethod(): void
    {
        if (! auth()->user()->can('purchase.edit')) {
            return;
        }

        $this->gr->payment_method = $this->paymentMethod;
        $this->gr->payment_reference = $this->paymentReference !== '' ? $this->paymentReference : null;
        $this->gr->save();

        if ($this->gr->isConfirmed()) {
            app(\App\Modules\Supplier\Services\GoodsReceiptService::class)->syncExpensePayment($this->gr);
        }

        // Subtle feedback without a full banner — just refresh
        $this->gr = $this->gr->fresh(['expense', 'items', 'purchaseOrder', 'supplier', 'receivedBy']);
    }

    public function render(): View
    {
        $subtotal = collect($this->lines)->sum(
            fn ($l) => (float) ($l['quantity_received'] ?? 0) * (float) ($l['unit_cost'] ?? 0)
        );

        return view('admin.receipts.show', compact('subtotal'));
    }
}