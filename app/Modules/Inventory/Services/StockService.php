<?php

namespace App\Modules\Inventory\Services;

use App\Models\User;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Inventory\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockService
{
    /**
     * Add stock (usually from a confirmed goods receipt).
     * Updates unit_cost on the inventory item to the latest purchase price.
     */
    public function stockIn(
        InventoryItem $item,
        float $quantity,
        string $reason,
        ?float $unitCost = null,
        ?Model $reference = null,
        ?User $performedBy = null,
        ?string $notes = null,
    ): StockMovement {
        if ($quantity <= 0) {
            throw new RuntimeException('Stock-in quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($item, $quantity, $reason, $unitCost, $reference, $performedBy, $notes) {
            $locked = InventoryItem::query()->lockForUpdate()->findOrFail($item->id);

            $before = (float) $locked->current_stock;
            $after  = $before + $quantity;

            $movement = StockMovement::create([
                'inventory_item_id' => $locked->id,
                'type'              => StockMovement::TYPE_IN,
                'quantity'          => $quantity,
                'balance_before'    => $before,
                'balance_after'     => $after,
                'unit_cost'         => $unitCost,
                'reason'            => $reason,
                'notes'             => $notes,
                'reference_type'    => $reference ? $reference::class : null,
                'reference_id'      => $reference?->getKey(),
                'created_by'        => $performedBy?->id,
            ]);

            $locked->current_stock = $after;
            if ($unitCost !== null && $unitCost > 0) {
                $locked->unit_cost = $unitCost;
            }
            $locked->save();

            return $movement;
        });
    }

    /**
     * Remove stock (kitchen consumption, prep, etc.).
     * Throws if this would take stock below zero.
     */
    public function stockOut(
        InventoryItem $item,
        float $quantity,
        string $reason,
        ?Model $reference = null,
        ?User $performedBy = null,
        ?string $notes = null,
    ): StockMovement {
        if ($quantity <= 0) {
            throw new RuntimeException('Stock-out quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($item, $quantity, $reason, $reference, $performedBy, $notes) {
            $locked = InventoryItem::query()->lockForUpdate()->findOrFail($item->id);

            $before = (float) $locked->current_stock;

            if ($before < $quantity) {
                throw new RuntimeException(sprintf(
                    'Insufficient stock for "%s". Available: %s %s, Required: %s %s',
                    $locked->name,
                    number_format($before, 3),
                    $locked->unit,
                    number_format($quantity, 3),
                    $locked->unit,
                ));
            }

            $after = $before - $quantity;

            $movement = StockMovement::create([
                'inventory_item_id' => $locked->id,
                'type'              => StockMovement::TYPE_OUT,
                'quantity'          => -$quantity,
                'balance_before'    => $before,
                'balance_after'     => $after,
                'reason'            => $reason,
                'notes'             => $notes,
                'reference_type'    => $reference ? $reference::class : null,
                'reference_id'      => $reference?->getKey(),
                'created_by'        => $performedBy?->id,
            ]);

            $locked->current_stock = $after;
            $locked->save();

            return $movement;
        });
    }

    /**
     * Record waste (spoilage, expiry, damage).
     * Same as stockOut but with the waste type for reporting.
     */
    public function waste(
        InventoryItem $item,
        float $quantity,
        string $reason,
        ?User $performedBy = null,
        ?string $notes = null,
    ): StockMovement {
        if ($quantity <= 0) {
            throw new RuntimeException('Waste quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($item, $quantity, $reason, $performedBy, $notes) {
            $locked = InventoryItem::query()->lockForUpdate()->findOrFail($item->id);

            $before = (float) $locked->current_stock;

            if ($before < $quantity) {
                throw new RuntimeException(sprintf(
                    'Cannot waste %s %s of "%s" — only %s %s available.',
                    number_format($quantity, 3), $locked->unit,
                    $locked->name,
                    number_format($before, 3), $locked->unit,
                ));
            }

            $after = $before - $quantity;

            $movement = StockMovement::create([
                'inventory_item_id' => $locked->id,
                'type'              => StockMovement::TYPE_WASTE,
                'quantity'          => -$quantity,
                'balance_before'    => $before,
                'balance_after'     => $after,
                'reason'            => $reason,
                'notes'             => $notes,
                'created_by'        => $performedBy?->id,
            ]);

            $locked->current_stock = $after;
            $locked->save();

            return $movement;
        });
    }

    /**
     * Correct the stock to a target level (physical count adjustment).
     * The "quantity" recorded on the movement is the signed delta.
     */
    public function adjust(
        InventoryItem $item,
        float $newQuantity,
        string $reason,
        ?User $performedBy = null,
        ?string $notes = null,
    ): StockMovement {
        if ($newQuantity < 0) {
            throw new RuntimeException('Target stock quantity cannot be negative.');
        }

        return DB::transaction(function () use ($item, $newQuantity, $reason, $performedBy, $notes) {
            $locked = InventoryItem::query()->lockForUpdate()->findOrFail($item->id);

            $before = (float) $locked->current_stock;
            $delta  = $newQuantity - $before;

            $movement = StockMovement::create([
                'inventory_item_id' => $locked->id,
                'type'              => StockMovement::TYPE_ADJUSTMENT,
                'quantity'          => $delta,
                'balance_before'    => $before,
                'balance_after'     => $newQuantity,
                'reason'            => $reason,
                'notes'             => $notes,
                'created_by'        => $performedBy?->id,
            ]);

            $locked->current_stock = $newQuantity;
            $locked->save();

            return $movement;
        });
    }
}