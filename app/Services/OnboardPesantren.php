<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\BillingSetting;
use App\Models\Pesantren;
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
     * 4. Aktifkan trial (durasi dari BillingSetting::trial_days)
     * 5. Isikan amalan bawaan supaya modul Mutaba'ah langsung bisa dipakai
     * 6. Catat audit log
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
                'nama_pesantren' => $namaPesantren,
                'slug' => $slug,
                'paket_langganan' => 'rintisan',
                'max_santri_kuota' => BillingSetting::get('kuota_rintisan', 100),
                'status_berlangganan' => 'trial',
                'expired_at' => now()->addDays(BillingSetting::get('trial_days', 14)),
                'santri_count_cache' => 0,
                'onboarding_completed_steps' => [],
            ]);

            // Subdomain + amalan bawaan. Dipisah ke ProvisionTenant supaya jalur
            // pembuatan lain (panel super admin) bisa memakai langkah yang sama
            // dan tidak lagi melahirkan tenant setengah jadi.
            app(ProvisionTenant::class)->jalankan($pesantren);

            $admin = User::create([
                'pesantren_id' => $pesantren->id,
                'name' => $adminName,
                'email' => $adminEmail,
                'phone_number' => $adminPhone,
                'password' => Hash::make($adminPassword),
                'role' => 'admin_pesantren',
            ]);

            ActivityLog::create([
                'pesantren_id' => $pesantren->id,
                'user_id' => $admin->id,
                'event' => 'pesantren.created',
                'auditable_type' => Pesantren::class,
                'auditable_id' => $pesantren->id,
                'new_values' => ['nama' => $namaPesantren, 'slug' => $slug],
            ]);

            return ['pesantren' => $pesantren, 'admin' => $admin];
        });
    }
}
