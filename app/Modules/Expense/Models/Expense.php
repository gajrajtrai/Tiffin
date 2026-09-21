<?php

namespace App\Modules\Expense\Models;

use App\Models\User;
use App\Modules\Supplier\Models\GoodsReceipt;
use App\Modules\Supplier\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Expense extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const METHOD_CASH          = 'cash';
    public const METHOD_BANK_TRANSFER = 'bank_transfer';
    public const METHOD_CHEQUE        = 'cheque';
    public const METHOD_OTHER         = 'other';

    public const STATUS_DRAFT    = 'draft';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PAID     = 'paid';

    public const MEDIA_RECEIPT = 'receipt';

    protected $fillable = [
        'expense_number', 'expense_category_id', 'supplier_id',
        'goods_receipt_id', 'expense_date', 'description',
        'amount', 'payment_method', 'payment_reference',
        'status', 'recorded_by', 'notes',
        'voided_at', 'voided_by', 'void_reason',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount'       => 'decimal:2',
            'voided_at'    => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $exp) {
            if (empty($exp->expense_number)) {
                $exp->expense_number = static::generateNumber($exp->expense_date ?? today());
            }
        });
    }

    protected static function generateNumber(Carbon|string $date): string
    {
        $dateStr = Carbon::parse($date)->format('Ymd');
        $base = 'EXP-'.$dateStr.'-';

        $attempt = 0;
        do {
            $count = static::where('expense_number', 'like', $base.'%')->count();
            $number = $base.str_pad((string) ($count + 1 + $attempt), 4, '0', STR_PAD_LEFT);
            $attempt++;
        } while (static::where('expense_number', $number)->exists() && $attempt < 100);

        return $number;
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Media
    |--------------------------------------------------------------------------
    */

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_RECEIPT)
             ->singleFile()
             ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
             ->width(300)
             ->nonQueued()
             ->performOnCollections(self::MEDIA_RECEIPT);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForDate(Builder $q, Carbon|string $date): Builder
    {
        return $q->whereDate('expense_date', Carbon::parse($date)->toDateString());
    }

    public function scopeForMonth(Builder $q, int $year, int $month): Builder
    {
        return $q->whereYear('expense_date', $year)
                 ->whereMonth('expense_date', $month);
    }

    public function scopeInCategory(Builder $q, int $categoryId): Builder
    {
        return $q->where('expense_category_id', $categoryId);
    }

    public function scopePaid(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_PAID);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->whereNull('voided_at');
    }

    public function scopeVoided(Builder $q): Builder
    {
        return $q->whereNotNull('voided_at');
    }

    public function scopeGrLinked(Builder $q): Builder
    {
        return $q->whereNotNull('goods_receipt_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    public function isGrLinked(): bool
    {
        return $this->goods_receipt_id !== null;
    }

    public function canBeVoided(): bool
    {
        return ! $this->isVoided() && ! $this->isGrLinked();
    }

    public function canBeDeleted(): bool
    {
        // Only drafts with no receipt and no GR link can be hard-deleted
        return ! $this->isGrLinked()
            && $this->status === self::STATUS_DRAFT
            && ! $this->hasMedia(self::MEDIA_RECEIPT);
    }

    public function statusLabel(): string
    {
        if ($this->isVoided()) {
            return 'Voided';
        }

        return match ($this->status) {
            self::STATUS_DRAFT    => 'Draft',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_PAID     => 'Paid',
            default               => ucfirst($this->status),
        };
    }

    public function statusVariant(): string
    {
        if ($this->isVoided()) {
            return 'danger';
        }

        return match ($this->status) {
            self::STATUS_DRAFT    => 'warning',
            self::STATUS_APPROVED => 'info',
            self::STATUS_PAID     => 'success',
            default               => 'slate',
        };
    }

    public function methodLabel(): string
    {
        return match ($this->payment_method) {
            self::METHOD_CASH          => 'Cash',
            self::METHOD_BANK_TRANSFER => 'Bank Transfer',
            self::METHOD_CHEQUE        => 'Cheque',
            self::METHOD_OTHER         => 'Other',
            default                    => ucfirst($this->payment_method),
        };
    }

    public function hasReceipt(): bool
    {
        return $this->hasMedia(self::MEDIA_RECEIPT);
    }
}