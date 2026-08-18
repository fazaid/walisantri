<?php

namespace Tests\Feature;

use App\Filament\Pages\UpgradePage;
use App\Models\BillingSetting;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Sejak v4.54 paket & durasi dipilih lewat kartu dan segmented control, bukan dua
 * Select. Perpindahan itu memindahkan pula dua pagar yang dulu menempel pada
 * komponen Filament: daftar durasi yang difilter `min_durasi_upgrade`, dan kuota
 * paket yang harus cukup untuk santri yang sudah masuk.
 */
class UpgradePagePilihanPaketTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(array $ubah = [], int $jumlahSantri = 0): Pesantren
    {
        $pesantren = Pesantren::factory()->create($ubah + [
            'paket_langganan' => 'tumbuh',
            'max_santri_kuota' => 250,
            'status_berlangganan' => 'active',
            'expired_at' => now()->addDays(20),
        ]);

        if ($jumlahSantri > 0) {
            Santri::factory()->count($jumlahSantri)->create([
                'pesantren_id' => $pesantren->id,
                'status_aktif' => true,
            ]);
        }

        $this->actingAs(User::factory()->create([
            'role' => 'admin_pesantren',
            'pesantren_id' => $pesantren->id,
        ]));

        return $pesantren;
    }

    /**
     * Kartu memajang angka yang sama dengan /harga — lewat PaketHargaService, bukan
     * ditulis di Blade. Setelannya digeser ke nilai tidak lazim lebih dulu supaya
     * angka mati ketahuan, pola yang sama dengan HargaPageTest.
     */
    public function test_kartu_memajang_harga_dan_kuota_dari_billing_setting(): void
    {
        BillingSetting::set('harga_tumbuh', 288_000);
        BillingSetting::set('kuota_tumbuh', 275);
        $this->tenant();

        Livewire::test(UpgradePage::class)
            ->assertSee('Rintisan')
            ->assertSee('Tumbuh')
            ->assertSee('Berkembang')
            ->assertSee('Maju')
            ->assertSee('Rp 288.000')
            ->assertSee('Sampai 275 santri')
            // Kartu memang tombol, bukan sekadar teks: tanpa asersi ini seluruh
            // kasus di atas masih hijau bila kartunya berubah jadi hiasan.
            ->assertSeeHtml('wire:click="pilihPaket(\'berkembang\')"')
            ->assertSeeHtml('wire:click="pilihDurasi(6)"');
    }

    public function test_ganti_siklus_mengubah_bulan_bayar_bonus_dan_total(): void
    {
        BillingSetting::set('harga_tumbuh', 300_000);
        BillingSetting::set('bonus_bulan_tahunan', 2);
        $this->tenant();

        Livewire::test(UpgradePage::class)
            ->assertSet('durasi_bulan', 1)
            ->call('pilihDurasi', 12)
            ->assertSet('durasi_bulan', 12)
            ->assertSet('bonus_bulan', 2)
            ->assertSet('bulan_bayar', 10)
            // Bonus dibayarkan sebagai bulan gratis: yang ditagih 10 bulan.
            ->assertSet('harga_total', 3_000_000);
    }

    /**
     * Pagar ini dulu dipegang `->options()` milik Select durasi yang memfilter
     * daftarnya; begitu dropdown-nya diganti tombol, ia harus hidup di pilihDurasi().
     */
    public function test_durasi_di_bawah_minimum_ditolak(): void
    {
        // Sisa aktif > 9 bulan → minimum 12 bulan (§16).
        $this->tenant(['expired_at' => now()->addMonths(11)]);

        Livewire::test(UpgradePage::class)
            ->assertSet('durasi_bulan', 12)
            ->call('pilihDurasi', 1)
            ->assertSet('durasi_bulan', 12)
            ->call('pilihDurasi', 3)
            ->assertSet('durasi_bulan', 12);
    }

    public function test_paket_berkuota_kurang_tidak_bisa_dipilih(): void
    {
        BillingSetting::set('kuota_rintisan', 5);
        $this->tenant(jumlahSantri: 6);

        Livewire::test(UpgradePage::class)
            ->call('pilihPaket', 'rintisan')
            ->assertSet('paket_target', 'tumbuh');
    }

    public function test_memilih_maju_memunculkan_kolom_kuota_dan_menaikkannya_ke_minimum(): void
    {
        $this->tenant();

        Livewire::test(UpgradePage::class)
            ->assertDontSee('Kuota Santri')
            ->call('pilihPaket', 'maju')
            ->assertSet('paket_target', 'maju')
            ->assertSet('max_santri_kuota_target', 1000)
            ->assertSee('Kuota Santri');
    }

    public function test_paket_tak_dikenal_diabaikan(): void
    {
        $this->tenant();

        Livewire::test(UpgradePage::class)
            ->call('pilihPaket', 'gratis')
            ->assertSet('paket_target', 'tumbuh');
    }
}
