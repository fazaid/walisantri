<?php

namespace Tests\Feature;

use App\Filament\Pages\BillingPage;
use App\Filament\Pages\UpgradePage;
use App\Models\BillingSetting;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Sejak v4.53 trial tidak lagi selalu Rintisan, sehingga arah paket bisa turun —
 * dan itu melahirkan keadaan yang sebelumnya mustahil: kuota target di bawah
 * jumlah santri yang sudah masuk. SantriObserver hanya menahan PENAMBAHAN santri;
 * tidak ada apa pun yang membereskan kelebihan yang terlanjur ada, jadi pagarnya
 * harus berdiri sebelum ordernya lahir.
 */
class PaketTrialKuotaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Kuota Rintisan sengaja dikecilkan supaya "melebihi kuota" cukup 6 santri,
     * bukan 101 baris yang tidak menambah apa pun selain waktu.
     */
    private function siapkanTenant(string $paket = 'tumbuh', int $jumlahSantri = 6): Pesantren
    {
        BillingSetting::set('kuota_rintisan', 5);

        $pesantren = Pesantren::factory()->create([
            'paket_langganan' => $paket,
            'max_santri_kuota' => 250,
            'status_berlangganan' => 'trial',
            'expired_at' => now()->addDays(10),
        ]);

        Santri::factory()->count($jumlahSantri)->create([
            'pesantren_id' => $pesantren->id,
            'status_aktif' => true,
        ]);

        $admin = User::factory()->create([
            'role' => 'admin_pesantren',
            'pesantren_id' => $pesantren->id,
        ]);

        $this->actingAs($admin);

        return $pesantren;
    }

    public function test_tombol_bayar_mati_saat_kuota_paket_tujuan_kurang(): void
    {
        $this->siapkanTenant();

        Livewire::test(UpgradePage::class)
            ->set('paket_target', 'rintisan')
            ->call('hitungHarga')
            ->assertSet('kuota_target_efektif', 5)
            ->assertActionDisabled('prosesPembayaran');
    }

    /**
     * Terpisah dari tes tombol di atas, dan itu disengaja: aksi Livewire tetap bisa
     * dipanggil langsung walau tombolnya mati, jadi pagar servernya harus diuji
     * tanpa asersi tombol yang akan menghentikan rantai lebih dulu.
     */
    public function test_order_ke_paket_berkuota_kurang_tidak_pernah_lahir(): void
    {
        $this->siapkanTenant();

        Livewire::test(UpgradePage::class)
            ->set('paket_target', 'rintisan')
            ->call('hitungHarga')
            ->call('prosesPembayaran');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_ke_paket_yang_kuotanya_cukup_tetap_jalan(): void
    {
        $this->siapkanTenant();

        Livewire::test(UpgradePage::class)
            ->set('paket_target', 'tumbuh')
            ->call('hitungHarga')
            ->assertActionEnabled('prosesPembayaran')
            ->call('prosesPembayaran');

        $this->assertDatabaseCount('orders', 1);
    }

    /**
     * Ganti paket saat trial TIDAK menyentuh expired_at — kalau masa trialnya ikut
     * direset, mengganti paket berulang kali jadi trial tanpa batas.
     */
    public function test_ganti_paket_trial_menjaga_sisa_hari(): void
    {
        $pesantren = $this->siapkanTenant();
        $expiredSemula = $pesantren->expired_at;

        Livewire::test(BillingPage::class)
            ->callAction('gantiPaketTrial', ['paket' => 'berkembang']);

        $pesantren->refresh();

        $this->assertSame('berkembang', $pesantren->paket_langganan);
        $this->assertSame(500, $pesantren->max_santri_kuota);
        $this->assertTrue($expiredSemula->equalTo($pesantren->expired_at));
    }

    public function test_ganti_paket_trial_menolak_kuota_yang_tidak_cukup(): void
    {
        $pesantren = $this->siapkanTenant();

        Livewire::test(BillingPage::class)
            ->callAction('gantiPaketTrial', ['paket' => 'rintisan']);

        $this->assertSame('tumbuh', $pesantren->refresh()->paket_langganan);
    }

    public function test_ganti_paket_trial_tidak_ditawarkan_ke_tenant_berbayar(): void
    {
        $pesantren = $this->siapkanTenant();
        $pesantren->update(['status_berlangganan' => 'active']);

        Livewire::test(BillingPage::class)
            ->assertActionHidden('gantiPaketTrial');
    }
}
