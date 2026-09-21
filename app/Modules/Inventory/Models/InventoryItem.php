<?php

namespace App\Modules\Inventory\Models;

use App\Concerns\LogsActivityChanges;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use SoftDeletes;
    use LogsActivityChanges;

    protected $fillable = [
        'name', 'sku', 'category', 'unit',
        'current_stock', 'reorder_level', 'unit_cost',
        'is_active', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'current_stock' => 'decimal:3',
            'reorder_level' => 'decimal:3',
            'unit_cost'     => 'decimal:2',
            'is_active'     => 'boolean',
        ];
    }

    protected function getActivitylogIdentifier(): ?string
    {
        return $this->name;
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(\App\Modules\Supplier\Models\PurchaseOrderItem::class);
    }

    public function goodsReceiptItems(): HasMany
    {
        return $this->hasMany(\App\Modules\Supplier\Models\GoodsReceiptItem::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeLowStock(Builder $q): Builder
    {
        return $q->whereColumn('current_stock', '<=', 'reorder_level')
                 ->where('reorder_level', '>', 0);
    }

    public function scopeCategory(Builder $q, string $category): Builder
    {
        return $q->where('category', $category);
    }

    public function scopeOrdered(Builder $q): Builder
    {
        return $q->orderBy('category')->orderBy('name');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isLowStock(): bool
    {
        return $this->reorder_level > 0
            && (float) $this->current_stock <= (float) $this->reorder_level;
    }

    public function isOutOfStock(): bool
    {
        return (float) $this->current_stock <= 0;
    }

    public function stockValue(): float
    {
        return (float) $this->current_stock * (float) $this->unit_cost;
    }

    public function displayStock(): string
    {
        $n = (float) $this->current_stock;
        return ($n == (int) $n ? (int) $n : number_format($n, 3)) . ' ' . $this->unit;
    }
}