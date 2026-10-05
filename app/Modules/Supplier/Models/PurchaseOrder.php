<?php

namespace App\Modules\Supplier\Models;

use App\Concerns\LogsActivityChanges;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class PurchaseOrder extends Model
{
    use LogsActivityChanges;

    public const STATUS_DRAFT              = 'draft';
    public const STATUS_SENT               = 'sent';
    public const STATUS_PARTIALLY_RECEIVED = 'partially_received';
    public const STATUS_RECEIVED           = 'received';
    public const STATUS_CANCELLED          = 'cancelled';

    protected $fillable = [
        'po_number', 'supplier_id', 'ordered_by',
        'order_date', 'expected_date', 'status',
        'subtotal', 'tax', 'total', 'notes',
        'sent_at', 'cancelled_at', 'cancelled_reason',
    ];

    protected function casts(): array
    {
        return [
            'order_date'     => 'date',
            'expected_date'  => 'date',
            'subtotal'       => 'decimal:2',
            'tax'            => 'decimal:2',
            'total'          => 'decimal:2',
            'sent_at'        => 'datetime',
            'cancelled_at'   => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $po) {
            if (empty($po->po_number)) {
                $po->po_number = static::generateNumber($po->order_date ?? today());
            }
        });
    }

    protected static function generateNumber(Carbon|string $date): string
    {
        // PYYMMDD-NN. Sequence resets daily. Padding is a minimum — a day
        // exceeding 99 POs naturally extends to 3 digits.
        $dateStr = Carbon::parse($date)->format('ymd');
        $base = 'P'.$dateStr.'-';

        $attempt = 0;
        do {
            $count = static::where('po_number', 'like', $base.'%')->count();
            $number = $base.str_pad((string) ($count + 1 + $attempt), 2, '0', STR_PAD_LEFT);
            $attempt++;
        } while (static::where('po_number', $number)->exists() && $attempt < 9999);

        return $number;
    }

    protected function getActivitylogIdentifier(): ?string
    {
        return $this->po_number;
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function orderedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ordered_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeOpen(Builder $q): Builder
    {
        return $q->whereIn('status', [self::STATUS_SENT, self::STATUS_PARTIALLY_RECEIVED]);
    }

    public function scopeForSupplier(Builder $q, int $supplierId): Builder
    {
        return $q->where('supplier_id', $supplierId);
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

    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function isReceived(): bool
    {
        return $this->status === self::STATUS_RECEIVED;
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SENT], true);
    }

    public function isEditable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT              => 'Draft',
            self::STATUS_SENT               => 'Sent',
            self::STATUS_PARTIALLY_RECEIVED => 'Partially Received',
            self::STATUS_RECEIVED           => 'Received',
            self::STATUS_CANCELLED          => 'Cancelled',
            default                         => ucfirst($this->status),
        };
    }

    public function statusVariant(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT              => 'slate',
            self::STATUS_SENT               => 'info',
            self::STATUS_PARTIALLY_RECEIVED => 'warning',
            self::STATUS_RECEIVED           => 'success',
            self::STATUS_CANCELLED          => 'danger',
            default                         => 'slate',
        };
    }

    public function recalculateStatus(): void
    {
        if ($this->status === self::STATUS_CANCELLED) {
            return;
        }

        $this->load('items');

        $allReceived = $this->items->every(fn ($i) => $i->quantity_received >= $i->quantity_ordered);
        $anyReceived = $this->items->some(fn ($i) => $i->quantity_received > 0);

        $this->status = match (true) {
            $allReceived => self::STATUS_RECEIVED,
            $anyReceived => self::STATUS_PARTIALLY_RECEIVED,
            default      => $this->status === self::STATUS_DRAFT ? self::STATUS_DRAFT : self::STATUS_SENT,
        };

        $this->save();
    }
}