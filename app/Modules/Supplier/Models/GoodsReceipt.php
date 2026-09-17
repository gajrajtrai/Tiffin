<?php

namespace App\Modules\Supplier\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class GoodsReceipt extends Model
{
    public const STATUS_DRAFT     = 'draft';
    public const STATUS_CONFIRMED = 'confirmed';

    protected $fillable = [
        'receipt_number', 'purchase_order_id', 'supplier_id',
        'received_by', 'received_date', 'status',
        'subtotal', 'tax', 'total', 'notes', 'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'received_date' => 'date',
            'subtotal'      => 'decimal:2',
            'tax'           => 'decimal:2',
            'total'         => 'decimal:2',
            'confirmed_at'  => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $gr) {
            if (empty($gr->receipt_number)) {
                $gr->receipt_number = static::generateNumber($gr->received_date ?? today());
            }
        });
    }

    protected static function generateNumber(Carbon|string $date): string
    {
        $dateStr = Carbon::parse($date)->format('Ymd');
        $base = 'GR-'.$dateStr.'-';

        $attempt = 0;
        do {
            $count = static::where('receipt_number', 'like', $base.'%')->count();
            $number = $base.str_pad((string) ($count + 1 + $attempt), 4, '0', STR_PAD_LEFT);
            $attempt++;
        } while (static::where('receipt_number', $number)->exists() && $attempt < 100);

        return $number;
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeConfirmed(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeDraft(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_DRAFT);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT     => 'Draft',
            self::STATUS_CONFIRMED => 'Confirmed',
            default                => ucfirst($this->status),
        };
    }

    public function statusVariant(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT     => 'warning',
            self::STATUS_CONFIRMED => 'success',
            default                => 'slate',
        };
    }
	    /**
     * Recompute header totals from the current line items.
     * Called automatically when confirming; call manually after editing items.
     */
    public function recalculateTotals(): void
    {
        $this->load('items');

        $this->subtotal = (float) $this->items->sum('line_total');
        $this->total    = $this->subtotal + (float) $this->tax;

        $this->save();
    }
}