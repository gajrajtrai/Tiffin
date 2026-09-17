<?php

namespace App\Modules\Menu\Models;

use App\Modules\Core\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ServiceDay extends Model
{
    protected $fillable = [
        'service_date', 'is_open',
        'delivery_cutoff_time', 'max_delivery_capacity', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'service_date'           => 'date',
            'is_open'                => 'boolean',
            'max_delivery_capacity'  => 'integer',
        ];
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

    public function scopeUpcoming(Builder $q): Builder
    {
        return $q->where('service_date', '>=', now()->toDateString())
                 ->orderBy('service_date');
    }

    /*
    |--------------------------------------------------------------------------
    | Static helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Get the service day record for a date, creating a default if missing.
     *
     * Default open/closed logic:
     *   - Weekday (Mon–Fri)   → open
     *   - Weekend (Sat–Sun)   → closed
     */
    public static function forDate(Carbon|string $date): self
    {
        $carbon = Carbon::parse($date);
        $dateString = $carbon->toDateString();

        return static::firstOrCreate(
            ['service_date' => $dateString],
            ['is_open' => $carbon->isWeekday()]
        );
    }

    /**
     * Convenience: is a date currently open for service?
     */
    public static function isOpenOn(Carbon|string $date): bool
    {
        return static::forDate($date)->is_open;
    }

    /*
    |--------------------------------------------------------------------------
    | Effective settings (fall back to Settings table when null)
    |--------------------------------------------------------------------------
    */

    /**
     * Order cut-off time in HH:MM (24h).
     */
    public function effectiveCutoffTime(): string
    {
        if ($this->delivery_cutoff_time) {
            return substr((string) $this->delivery_cutoff_time, 0, 5);
        }

        return (string) Setting::get('order_cutoff_time', '11:00');
    }

    /**
     * Max delivery orders for the day.
     */
    public function effectiveCapacity(): int
    {
        return $this->max_delivery_capacity
            ?? (int) Setting::get('delivery_capacity', 100);
    }

    /**
     * Have we passed the cut-off for this service day?
     */
    public function isPastCutoff(): bool
    {
        $cutoff = Carbon::parse(
            $this->service_date->toDateString().' '.$this->effectiveCutoffTime()
        );

        return now()->greaterThan($cutoff);
    }

    /**
     * Is delivery ordering currently allowed on this date?
     */
    public function acceptsDeliveryOrders(): bool
    {
        return $this->is_open && ! $this->isPastCutoff();
    }
}