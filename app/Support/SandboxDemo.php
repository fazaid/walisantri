<?php

namespace App\Support;

use App\Models\Pesantren;
use App\Models\Santri;
use Illuminate\Support\Facades\Cache;

/**
 * Titik tunggal untuk menemukan tenant sandbox publik dan tautan portal wali
 * contohnya.
 *
 * Tautannya TIDAK PERNAH di-hardcode di mana pun: ia diturunkan dari
 * `santri.uuid` saat dibutuhkan, sehingga penyegaran data mingguan
 * (sandbox:segarkan) tidak bisa mematikan tombol di landing. Hasilnya
 * di-cache supaya landing tidak menambah query ke setiap render.
 */
class SandboxDemo
{
    public const SLUG = 'demo';

    public const CACHE_KEY = 'sandbox:wali_url';

    public static function pesantren(): ?Pesantren
    {
        return Pesantren::where('slug', self::SLUG)->where('is_demo', true)->first();
    }

    /**
     * Santri yang tautan portal walinya dipublikasikan. Selalu NIS terkecil
     * supaya stabil lintas penyegaran.
     */
    public static function santriContoh(Pesantren $pesantren): ?Santri
    {
        return Santri::withoutGlobalScope('pesantren')
            ->where('pesantren_id', $pesantren->id)
            ->where('status_aktif', true)
            ->whereNotNull('wali_santri_id')
            ->orderBy('nis')
            ->first();
    }

    public static function waliUrl(): ?string
    {
        return Cache::remember(self::CACHE_KEY, 3600, function () {
            $pesantren = self::pesantren();

            if (! $pesantren) {
                return null;
            }

            return self::santriContoh($pesantren)?->linkWali();
        });
    }

    public static function lupakanCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
