<?php

namespace App\Modules\Inventory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    public const TYPE_IN         = 'in';
    public const TYPE_OUT        = 'out';
    public const TYPE_WASTE      = 'waste';
    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'inventory_item_id', 'type', 'quantity',
        'balance_before', 'balance_after',
        'unit_cost', 'reason', 'notes',
        'reference_type', 'reference_id', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity'       => 'decimal:3',
            'balance_before' => 'decimal:3',
            'balance_after'  => 'decimal:3',
            'unit_cost'      => 'decimal:2',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeType(Builder $q, string $type): Builder
    {
        return $q->where('type', $type);
    }

    public function scopeForItem(Builder $q, int $itemId): Builder
    {
        return $q->where('inventory_item_id', $itemId);
    }

    public function scopeRecent(Builder $q): Builder
    {
        return $q->orderByDesc('created_at');
    }

    public function scopeIncoming(Builder $q): Builder
    {
        return $q->where('type', self::TYPE_IN);
    }

    public function scopeOutgoing(Builder $q): Builder
    {
        return $q->whereIn('type', [self::TYPE_OUT, self::TYPE_WASTE]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isIncoming(): bool
    {
        return $this->type === self::TYPE_IN;
    }

    public function isOutgoing(): bool
    {
        return in_array($this->type, [self::TYPE_OUT, self::TYPE_WASTE], true);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_IN         => 'Stock In',
            self::TYPE_OUT        => 'Consumed',
            self::TYPE_WASTE      => 'Waste',
            self::TYPE_ADJUSTMENT => 'Adjustment',
            default               => ucfirst($this->type),
        };
    }

    public function typeVariant(): string
    {
        return match ($this->type) {
            self::TYPE_IN         => 'success',
            self::TYPE_OUT        => 'info',
            self::TYPE_WASTE      => 'danger',
            self::TYPE_ADJUSTMENT => 'warning',
            default               => 'slate',
        };
    }

    public function getSignedQuantityAttribute(): string
    {
        $sign = $this->quantity >= 0 ? '+' : '';
        return $sign . number_format((float) $this->quantity, 3);
    }
}