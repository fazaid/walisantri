<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class PlatformBrandingSetting extends Model
{
    protected $table = 'platform_branding_settings';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        return Cache::remember("platform_branding:{$key}", 3600, fn () => static::where('key', $key)->value('value') ?? $default
        );
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("platform_branding:{$key}");
    }

    /**
     * Nomor WhatsApp dukungan dalam bentuk siap-pakai untuk wa.me (62xxx, tanpa
     * tanda baca). Mengembalikan null bila belum diisi — tombol Bantuan di panel
     * ikut menghilang, bukan mengarah ke tautan rusak.
     *
     * Perapiannya di sini, bukan di form: super admin boleh mengetik
     * "0812-3456-7890" atau "+62 812 3456 7890" dan keduanya tetap benar.
     */
    public static function waDukungan(): ?string
    {
        $mentah = static::get('wa_dukungan');

        if (blank($mentah)) {
            return null;
        }

        $angka = preg_replace('/\D/', '', $mentah);

        if (str_starts_with($angka, '0')) {
            $angka = '62'.substr($angka, 1);
        }

        return $angka !== '' ? $angka : null;
    }

    // URL untuk <img src> di web. Fallback ke logo statis bawaan selama belum
    // pernah di-upload lewat panel super admin.
    public static function logoUrl(): string
    {
        $path = static::get('logo');

        return $path ? Storage::disk('public')->url($path) : asset('images/logo.svg');
    }

    // Path filesystem absolut — dipakai render PDF (DomPDF, enable_remote=false,
    // tak bisa fetch URL), sama pola dengan Pesantren::getLogoPathAttribute().
    public static function logoPath(): string
    {
        $path = static::get('logo');

        return $path ? Storage::disk('public')->path($path) : public_path('images/logo.svg');
    }

    // URL favicon untuk <link rel="icon"> di <head>. Fallback ke favicon.svg
    // statis bawaan selama belum pernah di-upload lewat panel super admin.
    public static function faviconUrl(): string
    {
        $path = static::get('favicon');

        return $path ? Storage::disk('public')->url($path) : asset('favicon.svg');
    }
}
