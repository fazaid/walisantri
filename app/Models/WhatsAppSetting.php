<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class WhatsAppSetting extends Model
{
    protected $table = 'whatsapp_settings';

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
        return Cache::remember("whatsapp_setting:{$key}", 3600, fn () => (bool) (static::where('key', $key)->value('value') ?? $default)
        );
    }

    public static function set(string $key, bool $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("whatsapp_setting:{$key}");
    }
}
