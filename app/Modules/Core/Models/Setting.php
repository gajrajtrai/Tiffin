<?php

namespace App\Modules\Core\Models;

use App\Concerns\LogsActivityChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use LogsActivityChanges;

    protected $fillable = [
        'key', 'value', 'type', 'group', 'label', 'description',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('settings.all'));
        static::deleted(fn () => Cache::forget('settings.all'));
    }

    protected function getActivitylogIdentifier(): ?string
    {
        return $this->key;
    }

    /**
     * All settings as key => typed value, cached until any setting changes.
     */
    public static function allCached(): array
    {
        return Cache::rememberForever('settings.all', function () {
            return static::query()
                ->get()
                ->mapWithKeys(fn (self $s) => [$s->key => $s->typedValue()])
                ->all();
        });
    }

    /**
     * Read a setting's typed value.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::allCached()[$key] ?? $default;
    }

    /**
     * Write a setting. Value is stored as string, type drives read-back cast.
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) ? json_encode($value) : (string) $value]
        );
    }

    /**
     * Write a JSON-valued setting. The type field is set to 'json'
     * so typedValue() decodes it automatically.
     */
    public static function setJson(string $key, array $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => json_encode($value), 'type' => 'json']
        );

        Cache::forget('settings.all');
    }

    /**
     * Write a setting with an explicit type. Unlike set(), this
     * always updates the type column — useful when the UI edits
     * a value whose type matters (int, decimal, etc.).
     */
    public static function setWithType(string $key, mixed $value, string $type = 'string'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : (string) $value,
                'type'  => $type,
            ]
        );

        Cache::forget('settings.all');
    }

    /**
     * Return the value cast according to its declared type.
     */
    public function typedValue(): mixed
    {
        return match ($this->type) {
            'integer' => (int) $this->value,
            'decimal' => (float) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json'    => json_decode($this->value ?? 'null', true),
            default   => $this->value,
        };
    }
}