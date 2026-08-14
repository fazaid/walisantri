<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Kill-switch per jenis email (§12.2) — satu baris per jenis, default menyala.
 *
 * Pola sama persis dengan WhatsAppSetting: memisahkan "apakah boleh kirim" dari
 * kredensial pengirimnya (EmailGatewaySetting), supaya super admin bisa mematikan
 * satu jenis email tanpa melumpuhkan seluruh kanal.
 */
class EmailSetting extends Model
{
    protected $table = 'email_settings';

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
        return Cache::remember("email_setting:{$key}", 3600, fn () => (bool) (static::where('key', $key)->value('value') ?? $default)
        );
    }

    public static function set(string $key, bool $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("email_setting:{$key}");
    }
}
