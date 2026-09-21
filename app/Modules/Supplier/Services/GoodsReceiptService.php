<?php

namespace App\Modules\Supplier\Services;

use App\Models\User;
use App\Modules\Expense\Models\Expense;
use App\Modules\Expense\Models\ExpenseCategory;
use App\Modules\Inventory\Services\StockService;
use App\Modules\Supplier\Models\GoodsReceipt;
use App\Modules\Supplier\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GoodsReceiptService
{
    public function __construct(
        protected StockService $stock,
    ) {}

    /**
     * Confirm a draft goods receipt:
     *   1. For each item, push stock into inventory via StockService.
     *   2. Update the linked PO line's quantity_received.
     *   3. Recalculate the PO status.
     *   4. Recalculate the receipt totals.
     *   5. Create (or refresh) the matching expense under Raw Materials.
     *   6. Mark the receipt as confirmed.
     *
     * @throws RuntimeException
     */
    public function confirm(GoodsReceipt $receipt, ?User $performedBy = null): GoodsReceipt
    {
        if ($receipt->isConfirmed()) {
            throw new RuntimeException('Receipt '.$receipt->receipt_number.' is already confirmed.');
        }

        if ($receipt->items()->count() === 0) {
            throw new RuntimeException('Cannot confirm a receipt with no items.');
        }

        return DB::transaction(function () use ($receipt, $performedBy) {
            $receipt->load(['items.inventoryItem', 'items.purchaseOrderItem', 'purchaseOrder.items']);

            // 1. Push stock in
            foreach ($receipt->items as $item) {
                if (! $item->inventoryItem) {
                    continue;
                }

                $this->stock->stockIn(
                    item: $item->inventoryItem,
                    quantity: (float) $item->quantity_received,
                    reason: 'Goods Receipt '.$receipt->receipt_number,
                    unitCost: $item->unit_cost !== null ? (float) $item->unit_cost : null,
                    reference: $item,
                    performedBy: $performedBy,
                    notes: $item->batch_code ? 'Batch '.$item->batch_code : null,
                );

                if ($item->purchaseOrderItem) {
                    $poItem = $item->purchaseOrderItem;
                    $poItem->quantity_received = (float) $poItem->quantity_received + (float) $item->quantity_received;
                    $poItem->save();
                }
            }

            // 2. Recalc PO status
            if ($receipt->purchaseOrder) {
                $receipt->purchaseOrder->recalculateStatus();
            }

            // 3. Refresh line totals and header
            $receipt->unsetRelation('items');
            $receipt->recalculateTotals();

            // 4. Mark receipt confirmed
            $receipt->status = GoodsReceipt::STATUS_CONFIRMED;
            $receipt->confirmed_at = now();
            if ($performedBy) {
                $receipt->received_by = $performedBy->id;
            }
            $receipt->save();

            // 5. Create / update the linked expense (Raw Materials)
            $this->createLinkedExpense($receipt, $performedBy);

            return $receipt->fresh(['items', 'purchaseOrder', 'expense']);
        });
    }

    /**
     * Auto-populate a receipt from an open PO.
     */
    public function buildFromPurchaseOrder(PurchaseOrder $po): GoodsReceipt
    {
        return DB::transaction(function () use ($po) {
            $po->load('items.inventoryItem');

            $receipt = GoodsReceipt::create([
                'purchase_order_id' => $po->id,
                'supplier_id'       => $po->supplier_id,
                'received_date'     => today(),
                'status'            => GoodsReceipt::STATUS_DRAFT,
                'payment_method'    => 'cash',
            ]);

            foreach ($po->items as $poItem) {
                $remaining = $poItem->remainingQuantity();

                if ($remaining <= 0) {
                    continue;
                }

                $receipt->items()->create([
                    'purchase_order_item_id' => $poItem->id,
                    'inventory_item_id'      => $poItem->inventory_item_id,
                    'item_name'              => $poItem->item_name,
                    'unit'                   => $poItem->unit,
                    'quantity_received'      => $remaining,
                    'unit_cost'              => $poItem->unit_cost,
                    'line_total'             => $remaining * (float) $poItem->unit_cost,
                ]);
            }

            $receipt->refresh();
            $receipt->subtotal = $receipt->items->sum('line_total');
            $receipt->total    = $receipt->subtotal;
            $receipt->save();

            return $receipt;
        });
    }

    /**
     * Update the linked expense when payment method or reference changes.
     * Called from GoodsReceiptDetail when those fields are updated.
     */
    public function syncExpensePayment(GoodsReceipt $receipt): void
    {
        $expense = Expense::where('goods_receipt_id', $receipt->id)->first();

        if (! $expense || $expense->isVoided()) {
            return;
        }

        $expense->payment_method = $receipt->payment_method ?: 'cash';
        $expense->payment_reference = $receipt->payment_reference;
        $expense->save();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Ensure a Raw Materials category exists, then create or refresh the
     * expense record that mirrors this goods receipt.
     */
    protected function createLinkedExpense(GoodsReceipt $receipt, ?User $performedBy): Expense
    {
        $category = ExpenseCategory::firstOrCreate(
            ['slug' => 'raw-materials'],
            [
                'name'        => 'Raw Materials',
                'color'       => 'brand',
                'description' => 'Auto-generated from goods receipts. Do not delete.',
                'sort_order'  => 1,
                'is_active'   => true,
            ]
        );

        $existing = Expense::where('goods_receipt_id', $receipt->id)->first();

        if ($existing) {
            // If the GR was re-confirmed after being edited, keep the linked
            // expense in sync — but never un-void a previously voided expense.
            if (! $existing->isVoided()) {
                $existing->update([
                    'expense_category_id' => $category->id,
                    'supplier_id'         => $receipt->supplier_id,
                    'expense_date'        => $receipt->received_date,
                    'description'         => 'Purchase — '.$receipt->receipt_number,
                    'amount'              => $receipt->total,
                    'payment_method'      => $receipt->payment_method ?: 'cash',
                    'payment_reference'   => $receipt->payment_reference,
                    'status'              => Expense::STATUS_PAID,
                ]);
            }

            return $existing;
        }

        return Expense::create([
            'expense_category_id' => $category->id,
            'supplier_id'         => $receipt->supplier_id,
            'goods_receipt_id'    => $receipt->id,
            'expense_date'        => $receipt->received_date,
            'description'         => 'Purchase — '.$receipt->receipt_number,
            'amount'              => $receipt->total,
            'payment_method'      => $receipt->payment_method ?: 'cash',
            'payment_reference'   => $receipt->payment_reference,
            'status'              => Expense::STATUS_PAID,
            'recorded_by'         => $performedBy?->id,
        ]);
    }
}