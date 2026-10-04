<?php

namespace App\Modules\Admin\Http\Livewire;

use App\Modules\Supplier\Models\PurchaseOrder;
use App\Modules\Supplier\Services\GoodsReceiptService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class PurchaseOrderDetail extends Component
{
    public PurchaseOrder $po;

    public ?string $statusMessage = null;
    public ?string $statusType = null;

    public string $cancelReason = '';

    public function mount(PurchaseOrder $po): void
    {
        Gate::authorize('view', $po);

        $this->po = $po->load(['supplier', 'items.inventoryItem', 'goodsReceipts']);
    }

    public function layoutData(): array
    {
        return [
            'title'   => 'PO '.$this->po->po_number,
            'heading' => 'Purchase Order',
        ];
    }

    protected function flash(string $type, string $message): void
    {
        $this->statusType = $type;
        $this->statusMessage = $message;
    }

    public function markSent(): void
    {
        if (! auth()->user()->can('send', $this->po)) {
            if (! $this->po->isDraft()) {
                $this->flash('error', 'Only draft POs can be marked as sent.');
                return;
            }
            abort(403);
        }

        $this->po->status = PurchaseOrder::STATUS_SENT;
        $this->po->sent_at = now();
        $this->po->save();

        $this->po->refresh();
        $this->flash('success', 'Purchase order marked as sent.');
    }

    public function cancel(): void
    {
        if (! auth()->user()->can('cancel', $this->po)) {
            if (! $this->po->isCancellable()) {
                $this->flash('error', 'This PO cannot be cancelled in its current state.');
                return;
            }
            abort(403);
        }

        $this->validate([
            'cancelReason' => 'required|string|min:3|max:255',
        ], [
            'cancelReason.required' => 'A reason is required.',
        ]);

        $this->po->status = PurchaseOrder::STATUS_CANCELLED;
        $this->po->cancelled_at = now();
        $this->po->cancelled_reason = $this->cancelReason;
        $this->po->save();

        $this->po->refresh();
        $this->reset('cancelReason');
        $this->dispatch('close-modal-cancel-po');
        $this->flash('success', 'Purchase order cancelled.');
    }

    /**
     * Create a draft Goods Receipt from this PO and redirect to it.
     */
    public function createReceipt()
    {
        if (! auth()->user()->can('receive', $this->po)) {
            if (! in_array($this->po->status, [
                PurchaseOrder::STATUS_SENT,
                PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
            ], true)) {
                $this->flash('error', 'This PO is not in a state to receive goods.');
                return null;
            }
            abort(403);
        }
        try {
            $gr = app(GoodsReceiptService::class)->buildFromPurchaseOrder($this->po);

            if ($gr->items()->count() === 0) {
                $gr->delete();
                $this->flash('error', 'Nothing left to receive on this PO.');
                return null;
            }

            return $this->redirect(route('admin.receipts.show', $gr), navigate: true);
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
            return null;
        }
    }

    public function render(): View
    {
        return view('admin.purchases.show');
    }
}