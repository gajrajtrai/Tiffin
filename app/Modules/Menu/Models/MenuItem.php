<?php

namespace App\Modules\Menu\Models;

use App\Concerns\LogsActivityChanges;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MenuItem extends Model implements HasMedia
{
    use SoftDeletes;
    use InteractsWithMedia;
    use LogsActivityChanges;

    public const MEDIA_IMAGE = 'image';

    protected $fillable = [
        'name', 'slug', 'description', 'type', 'is_veg',
        'price', 'image_path', 'sort_order', 'is_active',
        'daily_limit',
    ];

    protected function casts(): array
    {
        return [
            'is_veg'      => 'boolean',
            'is_active'   => 'boolean',
            'price'       => 'decimal:2',
            'sort_order'  => 'integer',
            'daily_limit' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $item) {
            if (empty($item->slug)) {
                $item->slug = static::uniqueSlug($item->name);
            }
        });

        static::updating(function (self $item) {
            if ($item->isDirty('name') && ! $item->isDirty('slug')) {
                $item->slug = static::uniqueSlug($item->name, $item->id);
            }
        });
    }

    protected static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (static::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    protected function getActivitylogIdentifier(): ?string
    {
        return $this->name;
    }

    /*
    |--------------------------------------------------------------------------
    | Media
    |--------------------------------------------------------------------------
    */

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_IMAGE)
             ->singleFile()
             ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
             ->width(300)
             ->height(300)
             ->sharpen(5)
             ->nonQueued();
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function dailyMenus(): HasMany
    {
        return $this->hasMany(DailyMenu::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(\App\Modules\Order\Models\OrderItem::class);
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

    public function scopeMains(Builder $q): Builder
    {
        return $q->where('type', 'main');
    }

    public function scopeFastFood(Builder $q): Builder
    {
        return $q->where('type', 'fastfood');
    }

    public function scopeVeg(Builder $q): Builder
    {
        return $q->where('is_veg', true);
    }

    public function scopeNonVeg(Builder $q): Builder
    {
        return $q->where('is_veg', false);
    }

    public function scopeOrdered(Builder $q): Builder
    {
        return $q->orderBy('sort_order')->orderBy('name');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isMain(): bool
    {
        return $this->type === 'main';
    }

    public function isFastFood(): bool
    {
        return $this->type === 'fastfood';
    }

    public function getImageUrlAttribute(): ?string
    {
        if ($this->hasMedia(self::MEDIA_IMAGE)) {
            return $this->getFirstMediaUrl(self::MEDIA_IMAGE);
        }

        return $this->image_path
            ? asset('storage/'.$this->image_path)
            : null;
    }

    public function isPublishedToday(): bool
    {
        return $this->dailyMenus()
            ->whereDate('service_date', today())
            ->exists();
    }
	    /**
     * A menu item can only be deleted if it has never been part of an
     * order. Otherwise the safe path is to set it inactive.
     */
    public function canBeDeleted(): bool
    {
        return ! $this->orderItems()->exists();
    }
	    /**
     * How many of this item have been ordered today (across all customers,
     * excluding cancelled orders)? Delivered/picked_up orders still count —
     * the food was made and consumed.
     */
    public function orderedToday(): int
    {
        return (int) \App\Modules\Order\Models\OrderItem::query()
            ->where('menu_item_id', $this->id)
            ->whereHas('order', function ($q) {
                $q->whereDate('service_date', today())
                  ->where('status', '!=', \App\Modules\Order\Models\Order::STATUS_CANCELLED);
            })
            ->sum('quantity');
    }

    /**
     * How many more can still be ordered today?
     * Returns null when no limit is set.
     */
    public function remainingToday(): ?int
    {
        if ($this->daily_limit === null) {
            return null;
        }

        return max(0, $this->daily_limit - $this->orderedToday());
    }

    /**
     * Has the daily limit been reached?
     */
    public function isLimitReached(): bool
    {
        return $this->daily_limit !== null && $this->remainingToday() <= 0;
    }
}