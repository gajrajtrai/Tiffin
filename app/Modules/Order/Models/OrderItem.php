<?php

namespace App\Modules\Order\Models;

use App\Modules\Menu\Models\MenuItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'menu_item_id',
        'item_name', 'item_price', 'is_veg', 'item_type',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'item_price' => 'decimal:2',
            'is_veg'     => 'boolean',
            'quantity'   => 'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isMain(): bool
    {
        return $this->item_type === 'main';
    }

    public function isVeg(): bool
    {
        return (bool) $this->is_veg;
    }
	public function lineTotal(): float
    {
    return (float) $this->item_price * (int) $this->quantity;
    }
}