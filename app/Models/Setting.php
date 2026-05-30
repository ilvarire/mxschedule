<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Get a setting value by key, with optional default.
     *
     * Results are cached indefinitely and busted whenever a value is updated
     * via setValue(). This avoids repeated DB queries in hot paths such as
     * QR validation, pass generation, and student dashboards.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever(self::cacheKey($key), function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a setting value (create or update) and flush the related cache entry.
     */
    public static function setValue(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value]
        );

        // Bust the cache so the next read reflects the new value immediately.
        Cache::forget(self::cacheKey($key));
    }

    /**
     * Flush all cached settings (useful after bulk imports or migrations).
     */
    public static function flushCache(): void
    {
        $keys = static::pluck('key');

        foreach ($keys as $key) {
            Cache::forget(self::cacheKey($key));
        }
    }

    /**
     * Build the cache key for a given setting key.
     */
    protected static function cacheKey(string $key): string
    {
        return 'setting:' . $key;
    }
}
