<?php

namespace App\Modules\Payment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WalletTransaction extends Model
{
    public const TYPE_CREDIT     = 'credit';
    public const TYPE_DEBIT      = 'debit';
    public const TYPE_REFUND     = 'refund';
    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'user_id', 'type', 'amount',
        'balance_before', 'balance_after',
        'description', 'reference_type', 'reference_id', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount'         => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after'  => 'decimal:2',
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

    public function scopeCredits(Builder $q): Builder
    {
        return $q->where('type', self::TYPE_CREDIT);
    }

    public function scopeDebits(Builder $q): Builder
    {
        return $q->where('type', self::TYPE_DEBIT);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isCredit(): bool
    {
        return in_array($this->type, [self::TYPE_CREDIT, self::TYPE_REFUND], true);
    }

    public function isDebit(): bool
    {
        return in_array($this->type, [self::TYPE_DEBIT, self::TYPE_ADJUSTMENT], true);
    }

    public function getSignedAmountAttribute(): string
    {
        return ($this->isCredit() ? '+' : '-').number_format((float) $this->amount, 2);
    }
}