<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PlatformSetting extends Model
{
    protected $table = 'platform_settings';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'value', 'keterangan'];

    protected function casts(): array
    {
        return ['value' => 'boolean'];
    }

    public static function get(string $key, bool $default = true): bool
    {
        return Cache::remember("platform_setting:{$key}", 3600, fn () => (bool) (static::where('key', $key)->value('value') ?? $default)
        );
    }

    public static function set(string $key, bool $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("platform_setting:{$key}");
    }

    public static function registrationOpen(): bool
    {
        return static::get('registration_open', config('app.registration_open', true));
    }
}
