<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class WhatsAppGatewaySetting extends Model
{
    protected $table = 'whatsapp_gateway_settings';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'encrypted'];
    }

    // find()->value dipakai (bukan query builder ->value('value')) supaya cast
    // 'encrypted' jalan lewat hydration Eloquent, tidak mengembalikan ciphertext mentah.
    public static function get(string $key): ?string
    {
        return Cache::remember("whatsapp_gateway_setting:{$key}", 3600, fn () => static::find($key)?->value);
    }

    public static function set(string $key, string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("whatsapp_gateway_setting:{$key}");
    }
}
