<?php

namespace App\Modules\Supplier\Models;

use App\Concerns\LogsActivityChanges;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;
    use LogsActivityChanges;

    protected $fillable = [
        'name', 'contact_person', 'mobile', 'email',
        'address', 'supplies', 'bank_account',
        'is_active', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(\App\Modules\Expense\Models\Expense::class);
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

    public function scopeOrdered(Builder $q): Builder
    {
        return $q->orderBy('name');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function getDisplayContactAttribute(): string
    {
        return $this->contact_person
            ? $this->contact_person.' · '.$this->mobile
            : (string) $this->mobile;
    }
}