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
    ];

    protected function casts(): array
    {
        return [
            'service_date'   => 'date',
            'total'          => 'decimal:2',
            'cancelled_at'   => 'datetime',
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
        $date = Carbon::parse($serviceDate)->format('Ymd');
        $base = 'TTF-'.$date.'-';

        $attempt = 0;
        do {
            $count = static::whereDate('service_date', $serviceDate)->count();
            $number = $base.str_pad((string) ($count + 1 + $attempt), 4, '0', STR_PAD_LEFT);
            $attempt++;
        } while (static::where('order_number', $number)->exists() && $attempt < 100);

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
}