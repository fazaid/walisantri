<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class BillingSetting extends Model
{
    protected $primaryKey = 'key';
    protected $keyType    = 'string';
    public    $incrementing = false;

    protected $fillable = ['key', 'value', 'keterangan'];

    protected function casts(): array
    {
        return ['value' => 'integer'];
    }

    public static function get(string $key, int $default = 0): int
    {
        return Cache::remember("billing_setting:{$key}", 3600, fn () =>
            static::where('key', $key)->value('value') ?? $default
        );
    }

    public static function set(string $key, int $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("billing_setting:{$key}");
    }

    public static function allAsArray(): array
    {
        return static::all()->pluck('value', 'key')->all();
    }

    public static function saveMany(array $data): void
    {
        foreach ($data as $key => $value) {
            static::set($key, (int) $value);
        }
    }
}
