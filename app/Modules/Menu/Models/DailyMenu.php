<?php

namespace App\Modules\Menu\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RuntimeException;

class DailyMenu extends Model
{
    protected $table = 'daily_menu';

    protected $fillable = [
        'menu_item_id', 'service_date', 'sold_out_at',
    ];

    protected function casts(): array
    {
        return [
            'service_date' => 'date',
            'sold_out_at'  => 'datetime',
        ];
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function scopeForDate(Builder $q, Carbon|string $date): Builder
    {
        return $q->where('service_date', Carbon::parse($date)->toDateString());
    }

    /*
    |--------------------------------------------------------------------------
    | Constants & publish helpers (unchanged)
    |--------------------------------------------------------------------------
    */

    public const MAX_MAINS_PER_DAY = 2;

    public static function publish(Carbon|string $date, array $menuItemIds): array
    {
        $dateString = Carbon::parse($date)->toDateString();

        $items = MenuItem::query()
            ->whereIn('id', $menuItemIds)
            ->get();

        $alreadyPublishedIds = static::query()
            ->forDate($dateString)
            ->pluck('menu_item_id')
            ->all();

        $newMains = $items
            ->where('type', 'main')
            ->whereNotIn('id', $alreadyPublishedIds)
            ->count();

        $existingMains = static::query()
            ->forDate($dateString)
            ->whereHas('menuItem', fn ($q) => $q->where('type', 'main'))
            ->count();

        $totalMains = $existingMains + $newMains;

        if ($totalMains > self::MAX_MAINS_PER_DAY) {
            throw new RuntimeException(
                'Cannot publish '.$totalMains.' mains for '.$dateString
                .'. Maximum is '.self::MAX_MAINS_PER_DAY.' per day.'
            );
        }

        foreach ($items as $item) {
            static::firstOrCreate([
                'menu_item_id' => $item->id,
                'service_date' => $dateString,
            ]);
        }

        return $items->pluck('id')->all();
    }

    public static function unpublish(Carbon|string $date, ?array $menuItemIds = null): int
    {
        $query = static::query()->forDate($date);

        if ($menuItemIds !== null) {
            $query->whereIn('menu_item_id', $menuItemIds);
        }

        return $query->delete();
    }

    public static function isPublished(Carbon|string $date, int $menuItemId): bool
    {
        return static::query()
            ->forDate($date)
            ->where('menu_item_id', $menuItemId)
            ->exists();
    }

    public static function mainCountForDate(Carbon|string $date): int
    {
        return static::query()
            ->forDate($date)
            ->whereHas('menuItem', fn ($q) => $q->where('type', 'main'))
            ->count();
    }

    /*
    |--------------------------------------------------------------------------
    | Sold-out helpers (new)
    |--------------------------------------------------------------------------
    */

    /**
     * Is a specific item sold out on a given date?
     */
    public static function isSoldOut(Carbon|string $date, int $menuItemId): bool
    {
        return static::query()
            ->forDate($date)
            ->where('menu_item_id', $menuItemId)
            ->whereNotNull('sold_out_at')
            ->exists();
    }

    /**
     * Mark an item sold out (or restock it) for a given date.
     * Returns true if it was marked sold out, false if it was restocked.
     */
    public static function toggleSoldOut(Carbon|string $date, int $menuItemId): bool
    {
        $daily = static::query()
            ->forDate($date)
            ->where('menu_item_id', $menuItemId)
            ->first();

        if (! $daily) {
            throw new RuntimeException('This item is not published for that date.');
        }

        $nowSoldOut = $daily->sold_out_at === null;
        $daily->sold_out_at = $nowSoldOut ? now() : null;
        $daily->save();

        return $nowSoldOut;
    }

    /**
     * Full published menu for a date, grouped by type.
     * Each item is annotated with `is_sold_out` so the view can show the badge.
     */
    public static function publishedForDate(Carbon|string $date): array
    {
        $dateString = Carbon::parse($date)->toDateString();

        $dailyMenus = static::query()
            ->whereDate('service_date', $dateString)
            ->get()
            ->keyBy('menu_item_id');

        $items = MenuItem::query()
            ->active()
            ->ordered()
            ->whereIn('id', $dailyMenus->keys())
            ->get()
            ->each(function (MenuItem $item) use ($dailyMenus) {
                $daily = $dailyMenus->get($item->id);
                $item->setAttribute('is_sold_out', $daily && $daily->sold_out_at !== null);
            });

        return [
            'main'     => $items->where('type', 'main')->values(),
            'fastfood' => $items->where('type', 'fastfood')->values(),
        ];
    }
}