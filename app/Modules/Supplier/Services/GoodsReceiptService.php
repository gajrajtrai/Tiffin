<?php

namespace App\Modules\Supplier\Services;

use App\Models\User;
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
     *   4. Mark the receipt as confirmed.
     *
     * Idempotency: throws if already confirmed.
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

            foreach ($receipt->items as $item) {
                if (! $item->inventoryItem) {
                    continue; // item without inventory link — skip stock update
                }

                // 1. Push stock in
                $this->stock->stockIn(
                    item: $item->inventoryItem,
                    quantity: (float) $item->quantity_received,
                    reason: 'Goods Receipt '.$receipt->receipt_number,
                    unitCost: $item->unit_cost !== null ? (float) $item->unit_cost : null,
                    reference: $item,
                    performedBy: $performedBy,
                    notes: $item->batch_code
                        ? 'Batch '.$item->batch_code
                        : null,
                );

                // 2. Update PO line received quantity
                if ($item->purchaseOrderItem) {
                    $poItem = $item->purchaseOrderItem;
                    $poItem->quantity_received = (float) $poItem->quantity_received + (float) $item->quantity_received;
                    $poItem->save();
                }
            }

            // 3. Recalc PO status
            if ($receipt->purchaseOrder) {
                $receipt->purchaseOrder->recalculateStatus();
            }

            // 4. Refresh line totals and recalc header
            $receipt->unsetRelation('items');
            $receipt->recalculateTotals();

            // 5. Mark receipt confirmed
            $receipt->status = GoodsReceipt::STATUS_CONFIRMED;
            $receipt->confirmed_at = now();
            if ($performedBy) {
                $receipt->received_by = $performedBy->id;
            }
            $receipt->save();

            return $receipt->fresh(['items', 'purchaseOrder']);
        });
    }

    /**
     * Auto-populate a receipt from an open PO.
     * Creates draft receipt items for each remaining (unfulfilled) PO line.
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
            ]);

            foreach ($po->items as $poItem) {
                $remaining = $poItem->remainingQuantity();

                if ($remaining <= 0) {
                    continue; // fully received
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
            $receipt->total    = $receipt->subtotal; // tax left null for now
            $receipt->save();

            return $receipt;
        });
    }
}