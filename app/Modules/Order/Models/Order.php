<?php

namespace App\Modules\Order\Models;

use App\Concerns\LogsActivityChanges;
use App\Models\User;
use App\Modules\Payment\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Order extends Model
{
    use LogsActivityChanges;

    // Status flow
    public const STATUS_PENDING    = 'pending';
    public const STATUS_CONFIRMED  = 'confirmed';
    public const STATUS_PREPARING  = 'preparing';
    public const STATUS_READY      = 'ready';
    public const STATUS_DELIVERED  = 'delivered';
    public const STATUS_PICKED_UP  = 'picked_up';
    public const STATUS_CANCELLED  = 'cancelled';

    // Delivery methods
    public const METHOD_DELIVERY = 'delivery';
    public const METHOD_PICKUP   = 'pickup';

    protected $fillable = [
        'order_number', 'user_id', 'service_date',
        'delivery_method', 'delivery_slot',
        'total', 'status', 'payment_status',
        'wallet_transaction_id', 'notes',
        'cancelled_at', 'cancelled_reason',
		'editable_until',
    ];

    protected function casts(): array
    {
        return [
            'service_date'   => 'date',
            'total'          => 'decimal:2',
            'cancelled_at'   => 'datetime',
            'editable_until' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber($order->service_date);
            }
        });
    }

    /**
     * Human-readable number: TTF-20260917-0001
     */
    protected static function generateOrderNumber(Carbon|string $serviceDate): string
    {
        // YYMMDD-NN. Sequence resets daily. Padding is a minimum — a day
        // that exceeds 99 orders naturally extends to 3 digits without code
        // change (e.g., 261005-100).
        $dateStr = Carbon::parse($serviceDate)->format('ymd');
        $base = $dateStr.'-';

        $attempt = 0;
        do {
            $count = static::whereDate('service_date', $serviceDate)->count();
            $number = $base.str_pad((string) ($count + 1 + $attempt), 2, '0', STR_PAD_LEFT);
            $attempt++;
        } while (static::where('order_number', $number)->exists() && $attempt < 9999);

        return $number;
    }

    /*
    |--------------------------------------------------------------------------
    | Activity log identifier — used by the audit log viewer
    |--------------------------------------------------------------------------
    */

    protected function getActivitylogIdentifier(): ?string
    {
        return $this->order_number;
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForDate(Builder $q, Carbon|string $date): Builder
    {
        return $q->whereDate('service_date', Carbon::parse($date)->toDateString());
    }

    public function scopeForToday(Builder $q): Builder
    {
        return $q->forDate(today());
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->whereNotIn('status', [self::STATUS_CANCELLED, self::STATUS_DELIVERED, self::STATUS_PICKED_UP]);
    }

    public function scopeDeliveries(Builder $q): Builder
    {
        return $q->where('delivery_method', self::METHOD_DELIVERY);
    }

    public function scopePickups(Builder $q): Builder
    {
        return $q->where('delivery_method', self::METHOD_PICKUP);
    }

    /*
    |--------------------------------------------------------------------------
    | Status helpers
    |--------------------------------------------------------------------------
    */

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, [self::STATUS_DELIVERED, self::STATUS_PICKED_UP], true);
    }

    public function isActive(): bool
    {
        return ! $this->isCancelled() && ! $this->isCompleted();
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED], true);
    }

    public function isDelivery(): bool
    {
        return $this->delivery_method === self::METHOD_DELIVERY;
    }

    public function isPickup(): bool
    {
        return $this->delivery_method === self::METHOD_PICKUP;
    }

    /*
    |--------------------------------------------------------------------------
    | Display helpers
    |--------------------------------------------------------------------------
    */

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING   => 'Pending',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_PREPARING => 'Preparing',
            self::STATUS_READY     => 'Ready',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_PICKED_UP => 'Picked Up',
            self::STATUS_CANCELLED => 'Cancelled',
            default                => ucfirst($this->status),
        };
    }

    public function statusVariant(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING   => 'warning',
            self::STATUS_CONFIRMED => 'info',
            self::STATUS_PREPARING => 'brand',
            self::STATUS_READY     => 'success',
            self::STATUS_DELIVERED,
            self::STATUS_PICKED_UP => 'slate',
            self::STATUS_CANCELLED => 'danger',
            default                => 'slate',
        };
    }
	    /*
    |--------------------------------------------------------------------------
    | Edit window helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Can the customer still edit this order?
     *
     * Requirements:
     *   - Status must be pending (not yet forwarded to kitchen)
     *   - The edit window must not have expired
     */
    public function isEditable(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        if (! $this->editable_until) {
            return false;
        }

        return $this->editable_until->isFuture();
    }

    /**
     * Seconds remaining in the edit window. Zero if not editable.
     */
    public function editSecondsRemaining(): int
    {
        if (! $this->isEditable()) {
            return 0;
        }

        return max(0, $this->editable_until->getTimestamp() - now()->getTimestamp());
    }
	    /*
    |--------------------------------------------------------------------------
    | Display reference
    |--------------------------------------------------------------------------
    |
    | Short, spoken-friendly identifier: month letter + sequence without leading
    | zeros. Example: J47 (October, 47th order of the day).
    |
    | Falls back gracefully if the order_number doesn't parse (e.g., legacy data).
    |
    */

    public function getDisplayRefAttribute(): string
    {
        $parts = explode('-', (string) $this->order_number);

        if (count($parts) !== 2 || ! is_numeric($parts[1])) {
            return (string) $this->order_number;
        }

        $sequence = (int) $parts[1];

        // A = January, B = February, ... L = December
        $monthLetter = chr(64 + (int) $this->service_date->format('n'));

        return $monthLetter.$sequence;
    }
}