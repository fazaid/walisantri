<?php

namespace App\Services;

use App\Models\Pesantren;
use App\Models\TenantDomain;
use App\Support\AmalanDefault;

/**
 * Melengkapi pesantren yang baru dibuat agar siap dipakai.
 *
 * Dulu langkah ini hanya hidup di dalam OnboardPesantren, sehingga pesantren yang
 * dibuat lewat panel super admin lahir setengah jadi:
 *
 * - Tanpa baris tenant_domains, {slug}.walisantri.com 404 SELAMANYA. PublicTenantResolver
 *   mencocokkan hostname ke tabel itu, dan PesantrenObserver::updated hanya meng-UPDATE
 *   baris yang sudah ada (0 baris terpengaruh), jadi mengganti slug pun tidak menyembuhkan.
 * - Tanpa amalan bawaan, modul Mutaba'ah lumpuh diam-diam: grid Isi Harian tanpa kolom,
 *   form tanpa checkbox, skor selalu 0%.
 *
 * Keduanya idempoten, jadi aman dipanggil berulang dari jalur mana pun.
 */
class ProvisionTenant
{
    public function jalankan(Pesantren $pesantren): void
    {
        TenantDomain::firstOrCreate(
            [
                'pesantren_id' => $pesantren->id,
                'type' => 'subdomain',
                'is_primary' => true,
            ],
            [
                'hostname' => "{$pesantren->slug}.".config('app.base_domain', 'walisantri.com'),
                'ssl_status' => 'pending',
            ],
        );

        AmalanDefault::untukPesantren($pesantren->id);
    }
}
