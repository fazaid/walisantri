<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class WhatsAppMessageTemplate extends Model
{
    protected $table = 'whatsapp_message_templates';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'template'];

    public static function get(string $key, string $default = ''): string
    {
        return Cache::remember("whatsapp_template:{$key}", 3600, fn () => static::where('key', $key)->value('template') ?? $default
        );
    }

    public static function set(string $key, string $template): void
    {
        static::updateOrCreate(['key' => $key], ['template' => $template]);
        Cache::forget("whatsapp_template:{$key}");
    }
}
