<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Pengaturan tracking analytics (Google Tag Manager / Google Analytics 4).
 * Central / global — satu nilai untuk seluruh platform (bukan per-pesantren).
 * Pola key-value + cache mengikuti PlatformBrandingSetting.
 */
class AnalyticsSetting extends Model
{
    protected $table = 'analytics_settings';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        return Cache::remember("analytics_setting:{$key}", 3600, fn () => static::where('key', $key)->value('value') ?? $default);
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("analytics_setting:{$key}");
    }

    // GTM Container ID (GTM-XXXX) — null bila belum diatur.
    public static function gtmId(): ?string
    {
        return static::get('gtm_id');
    }

    // GA4 Measurement ID (G-XXXX) — null bila belum diatur.
    public static function ga4Id(): ?string
    {
        return static::get('ga4_id');
    }

    // Master switch. Default aktif; hanya nonaktif bila di-set eksplisit '0'.
    public static function enabled(): bool
    {
        return static::get('enabled', '1') !== '0';
    }
}
