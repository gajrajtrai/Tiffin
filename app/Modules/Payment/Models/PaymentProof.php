<?php

namespace App\Modules\Payment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PaymentProof extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const MEDIA_SCREENSHOT = 'screenshot';

    protected $fillable = [
        'user_id', 'claimed_amount', 'bank_reference', 'note',
        'status', 'reviewed_by', 'reviewed_at',
        'rejection_reason', 'wallet_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'claimed_amount' => 'decimal:2',
            'reviewed_at'    => 'datetime',
        ];
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

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Media
    |--------------------------------------------------------------------------
    */

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_SCREENSHOT)
             ->singleFile()
             ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/heic']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
             ->width(400)
             ->nonQueued();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePending(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_REJECTED);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING  => 'Pending review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            default               => ucfirst($this->status),
        };
    }

    public function statusVariant(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING  => 'warning',
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            default               => 'slate',
        };
    }
}