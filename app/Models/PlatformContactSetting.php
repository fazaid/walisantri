<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PlatformContactSetting extends Model
{
    protected $table = 'platform_contact_settings';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'value', 'keterangan'];

    public static function get(string $key, ?string $default = null): ?string
    {
        return Cache::remember("platform_contact_setting:{$key}", 3600, fn () => static::where('key', $key)->value('value') ?? $default);
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("platform_contact_setting:{$key}");
    }

    // Nomor WA CS dalam format internasional tanpa '+' (mis. 6281399096658),
    // siap dipakai sebagai https://wa.me/{nomor}. Null bila belum diatur.
    public static function csWhatsapp(): ?string
    {
        return static::get('cs_whatsapp');
    }
}
