<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Pesantren;
use App\Models\TenantDomain;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OnboardPesantren
{
    /**
     * Jalankan seluruh alur registrasi pesantren baru (PRD §4.1):
     * 1. Buat baris pesantrens
     * 2. Buat baris tenant_domains (subdomain otomatis)
     * 3. Buat user pertama role admin_pesantren
     * 4. Aktifkan trial 30 hari
     * 5. Catat audit log
     */
    public function execute(
        string $namaPesantren,
        string $slug,
        string $adminName,
        string $adminEmail,
        string $adminPassword,
        ?string $adminPhone = null,
    ): array {
        return DB::transaction(function () use (
            $namaPesantren, $slug, $adminName, $adminEmail, $adminPassword, $adminPhone
        ) {
            $pesantren = Pesantren::create([
                'nama_pesantren'     => $namaPesantren,
                'slug'               => $slug,
                'paket_langganan'    => 'gratis',
                'max_santri_kuota'   => 5,
                'status_berlangganan' => 'trial',
                'expired_at'         => now()->addDays(30),
                'santri_count_cache' => 0,
                'onboarding_completed_steps' => [],
            ]);

            TenantDomain::create([
                'pesantren_id' => $pesantren->id,
                'hostname'     => "{$slug}.walisantri.com",
                'type'         => 'subdomain',
                'is_primary'   => true,
                'ssl_status'   => 'pending',
            ]);

            $admin = User::create([
                'pesantren_id' => $pesantren->id,
                'name'         => $adminName,
                'email'        => $adminEmail,
                'phone_number' => $adminPhone,
                'password'     => Hash::make($adminPassword),
                'role'         => 'admin_pesantren',
            ]);

            ActivityLog::create([
                'pesantren_id'   => $pesantren->id,
                'user_id'        => $admin->id,
                'event'          => 'pesantren.created',
                'auditable_type' => Pesantren::class,
                'auditable_id'   => $pesantren->id,
                'new_values'     => ['nama' => $namaPesantren, 'slug' => $slug],
            ]);

            return ['pesantren' => $pesantren, 'admin' => $admin];
        });
    }
}
