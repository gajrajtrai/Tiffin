<?php

namespace App\Modules\Menu\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RuntimeException;

class DailyMenu extends Model
{
    /**
     * Explicit table name — "daily_menu" is singular by design (it's a pivot-ish table).
     */
    protected $table = 'daily_menu';

    protected $fillable = [
        'menu_item_id', 'service_date',
    ];

    protected function casts(): array
    {
        return [
            'service_date' => 'date',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForDate(Builder $q, Carbon|string $date): Builder
    {
        return $q->where('service_date', Carbon::parse($date)->toDateString());
    }

    /*
    |--------------------------------------------------------------------------
    | Static helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Maximum main courses allowed per service day.
     */
    public const MAX_MAINS_PER_DAY = 2;

    /**
     * Publish a list of menu items for a given date.
     *
     * Enforces the max-2-mains rule. Returns the array of published item IDs.
     * Throws if the rule would be violated.
     *
     * @param  array<int>  $menuItemIds
     * @return array<int>
     */
        public static function publish(Carbon|string $date, array $menuItemIds): array
    {
        $dateString = Carbon::parse($date)->toDateString();

        $items = MenuItem::query()
            ->whereIn('id', $menuItemIds)
            ->get();

        // Which mains are NOT already published for this date?
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

    /**
     * Unpublish items for a date. Pass null to clear the entire day.
     *
     * @param  array<int>|null  $menuItemIds
     */
    public static function unpublish(Carbon|string $date, ?array $menuItemIds = null): int
    {
        $query = static::query()->forDate($date);

        if ($menuItemIds !== null) {
            $query->whereIn('menu_item_id', $menuItemIds);
        }

        return $query->delete();
    }

    /**
     * Is a specific item published on a given date?
     */
    public static function isPublished(Carbon|string $date, int $menuItemId): bool
    {
        return static::query()
            ->forDate($date)
            ->where('menu_item_id', $menuItemId)
            ->exists();
    }

    /**
     * Number of mains currently published for a date.
     */
    public static function mainCountForDate(Carbon|string $date): int
    {
        return static::query()
            ->forDate($date)
            ->whereHas('menuItem', fn ($q) => $q->where('type', 'main'))
            ->count();
    }

    /**
     * Full published menu for a date, grouped by type.
     *
     * @return array{main: \Illuminate\Support\Collection, fastfood: \Illuminate\Support\Collection}
     */
    public static function publishedForDate(Carbon|string $date): array
    {
        $items = MenuItem::query()
            ->active()
            ->ordered()
            ->whereHas('dailyMenus', fn ($q) => $q->forDate($date))
            ->get();

        return [
            'main'     => $items->where('type', 'main')->values(),
            'fastfood' => $items->where('type', 'fastfood')->values(),
        ];
    }
}