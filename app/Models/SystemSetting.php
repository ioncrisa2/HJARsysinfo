<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Get all settings as an associative array (cached).
     */
    public static function getAll()
    {
        return Cache::rememberForever('system_settings', function () {
            return self::pluck('value', 'key')->toArray();
        });
    }

    /**
     * Get a specific setting by key.
     */
    public static function get($key, $default = null)
    {
        $settings = self::getAll();
        return $settings[$key] ?? $default;
    }

    /**
     * Get a setting directly from the database, bypassing cache.
     */
    public static function getFresh($key, $default = null)
    {
        return self::query()
            ->where('key', $key)
            ->value('value') ?? $default;
    }

    /**
     * Set a specific setting by key.
     */
    public static function set($key, $value)
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forget('system_settings');
    }

    /**
     * Set multiple settings at once.
     */
    public static function setMany(array $settings)
    {
        foreach ($settings as $key => $value) {
            self::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        Cache::forget('system_settings');
    }
}
