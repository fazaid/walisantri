<?php

namespace App\Services;

use App\Enums\PaketLangganan;
use App\Models\ActivityLog;
use App\Models\BillingSetting;
use App\Models\Pesantren;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class OnboardPesantren
{
    /**
     * Jalankan seluruh alur registrasi pesantren baru (PRD §4.1):
     * 1. Buat baris pesantrens (termasuk profil awal: wilayah + kontak dari form)
     * 2. Buat baris tenant_domains (subdomain otomatis)
     * 3. Buat user pertama role admin_pesantren
     * 4. Aktifkan trial pada paket yang dipilih pendaftar (durasi dari
     *    BillingSetting::trial_days, kuota dari BillingCalculatorService)
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
        // Blob profil awal (§3.1): wilayah 4 level + telepon/email pesantren, dirakit
        // pemanggil. Satu parameter array, bukan enam skalar — bentuknya memang sudah
        // dimiliki bersama PesantrenSettingsPage, jadi menambah key baru kelak tidak
        // lagi mengubah tanda tangan ini. null untuk jalur non-registrasi.
        ?array $profil = null,
        // Paket yang di-trial. Sampai v4.52 selalu Rintisan — pesantren 300 santri
        // mencoba produk di kuota 100 dan menabrak SantriQuotaExceededException
        // sebelum sempat merasakan nilainya. Parameter terakhir dan bernilai bawaan
        // supaya jalur non-registrasi tidak perlu tahu apa-apa soal ini.
        PaketLangganan $paket = PaketLangganan::Rintisan,
    ): array {
        // Pagar terakhir, bukan pengganti validasi form: kuota paket Maju adalah
        // angka hasil negosiasi (add-on per 100 santri) yang service ini tidak punya,
        // jadi ia tidak boleh bisa lahir dari jalur pendaftaran mandiri sama sekali.
        if (! $paket->bisaDipilihSendiri()) {
            throw new InvalidArgumentException("Paket {$paket->value} tidak bisa didaftarkan mandiri.");
        }

        // Kuota lewat kalkulator, bukan PaketLangganan::maxSantri(): angka di enum itu
        // hardcode dan akan menyimpang begitu super admin menggeser kuota di
        // BillingSettingsPage — halaman /harga sudah membaca sumber yang sama.
        $kuota = app(BillingCalculatorService::class)
            ->hitungUntukTarget($paket->value, 0)['kuota_maksimal'];

        return DB::transaction(function () use (
            $namaPesantren, $slug, $adminName, $adminEmail, $adminPassword, $adminPhone, $profil, $paket, $kuota
        ) {
            $pesantren = Pesantren::create([
                'nama_pesantren' => $namaPesantren,
                'slug' => $slug,
                'paket_langganan' => $paket->value,
                'max_santri_kuota' => $kuota,
                'status_berlangganan' => 'trial',
                'expired_at' => now()->addDays(BillingSetting::get('trial_days', 14)),
                'santri_count_cache' => 0,
                'onboarding_completed_steps' => [],
                'profil' => $profil,
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
                'new_values' => ['nama' => $namaPesantren, 'slug' => $slug, 'paket' => $paket->value],
            ]);

            return ['pesantren' => $pesantren, 'admin' => $admin];
        });
    }
}
