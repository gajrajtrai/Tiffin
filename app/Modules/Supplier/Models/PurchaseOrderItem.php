<?php

namespace App\Modules\Supplier\Models;

use App\Modules\Inventory\Models\InventoryItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id', 'inventory_item_id',
        'item_name', 'unit',
        'quantity_ordered', 'quantity_received',
        'unit_cost', 'line_total', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_ordered'  => 'decimal:3',
            'quantity_received' => 'decimal:3',
            'unit_cost'         => 'decimal:2',
            'line_total'        => 'decimal:2',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function remainingQuantity(): float
    {
        return max(0, (float) $this->quantity_ordered - (float) $this->quantity_received);
    }

    public function isFullyReceived(): bool
    {
        return (float) $this->quantity_received >= (float) $this->quantity_ordered;
    }

    public function isPartiallyReceived(): bool
    {
        return (float) $this->quantity_received > 0 && ! $this->isFullyReceived();
    }
}