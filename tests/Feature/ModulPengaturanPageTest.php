<?php

namespace Tests\Feature;

use App\Enums\Modul;
use App\Filament\Pages\ModulPengaturanPage;
use App\Models\ModulPengaturan;
use App\Models\Pesantren;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Halaman tempat admin pesantren mematikan modul yang tidak ia pakai.
 *
 * Dua hal yang dikunci di sini dan tidak terlihat dari membaca kodenya:
 *
 * 1. Halaman ini TIDAK PERNAH ikut dimatikan modul. Kalau ia ikut hilang saat
 *    keenam modul dimatikan, admin kehilangan satu-satunya jalan kembali.
 * 2. Properti toggle-nya `?bool`, bukan `bool`. Toggle yang kembali sebagai null
 *    pernah memecahkan render halaman Pengaturan Presensi sebelum satu pun pesan
 *    validasi sempat muncul (changelog v4.28/v4.39).
 */
class ModulPengaturanPageTest extends TestCase
{
    use RefreshDatabase;

    private Pesantren $pesantren;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pesantren = Pesantren::factory()->create();
    }

    private function admin(): User
    {
        return User::factory()->adminPesantren()->create(['pesantren_id' => $this->pesantren->id]);
    }

    public function test_hanya_admin_pesantren_yang_bisa_membuka_halaman(): void
    {
        $this->actingAs($this->admin());
        $this->assertTrue(ModulPengaturanPage::canAccess());

        $this->actingAs(User::factory()->ustadz()->create(['pesantren_id' => $this->pesantren->id]));
        $this->assertFalse(ModulPengaturanPage::canAccess());

        $this->actingAs(User::factory()->superAdmin()->create());
        $this->assertFalse(ModulPengaturanPage::canAccess());

        $this->actingAs(User::factory()->waliSantri()->create(['pesantren_id' => $this->pesantren->id]));
        $this->assertFalse(ModulPengaturanPage::canAccess());
    }

    public function test_toggle_terisi_dari_baris_pengaturan(): void
    {
        ModulPengaturan::untuk($this->pesantren->id)->update([
            'keuangan_aktif' => false,
            'tahfidz_aktif' => false,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ModulPengaturanPage::class)
            ->assertSet('keuangan_aktif', false)
            ->assertSet('tahfidz_aktif', false)
            ->assertSet('presensi_aktif', true);
    }

    public function test_admin_bisa_mematikan_dan_menyalakan_kembali_modul(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(ModulPengaturanPage::class)
            ->set('keuangan_aktif', false)
            ->set('rapor_aktif', false)
            ->call('save')
            ->assertHasNoErrors();

        $pengaturan = ModulPengaturan::untuk($this->pesantren->id);
        $this->assertFalse($pengaturan->keuangan_aktif);
        $this->assertFalse($pengaturan->rapor_aktif);
        $this->assertTrue($pengaturan->akademik_aktif);

        Livewire::actingAs($admin)
            ->test(ModulPengaturanPage::class)
            ->set('keuangan_aktif', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(ModulPengaturan::untuk($this->pesantren->id)->keuangan_aktif);
    }

    /**
     * Regresi `?bool`. Toggle yang statenya kembali null tidak boleh meledak saat
     * di-assign ke properti — gejalanya "Cannot assign null to property of type bool"
     * dan halaman gagal dirender sebelum validasi sempat bicara.
     */
    public function test_toggle_bernilai_null_tidak_memecahkan_halaman(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ModulPengaturanPage::class)
            ->set('presensi_aktif', null)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSuccessful();

        // null diperlakukan sebagai "dimatikan", bukan dilewati diam-diam.
        $this->assertFalse(ModulPengaturan::untuk($this->pesantren->id)->presensi_aktif);
    }

    /**
     * Sidebar, tab cluster, dan bottom-nav HP dirender di layout halaman — di luar
     * komponen Livewire ini. Tanpa redirect, admin menyimpan lalu tetap melihat menu
     * yang baru saja ia matikan sampai menekan refresh sendiri, dan gejalanya
     * terbaca sebagai "toggle-nya tidak bekerja" padahal datanya sudah tersimpan.
     */
    public function test_menyimpan_memuat_ulang_halaman_supaya_menu_ikut_berubah(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ModulPengaturanPage::class)
            ->set('keuangan_aktif', false)
            ->call('save')
            ->assertRedirect(ModulPengaturanPage::getUrl());
    }

    /** Notification::send() menulis ke session, jadi ia selamat melewati redirect. */
    public function test_notifikasi_selamat_melewati_muat_ulang(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ModulPengaturanPage::class)
            ->set('keuangan_aktif', false)
            ->call('save');

        $this->assertNotEmpty(session('filament.notifications'));
        $this->assertStringContainsString(
            'Pengaturan modul tersimpan.',
            json_encode(session('filament.notifications'))
        );
    }

    public function test_perubahan_modul_tercatat_di_activity_log(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ModulPengaturanPage::class)
            ->set('kesantrian_aktif', false)
            ->call('save');

        $this->assertDatabaseHas('activity_logs', [
            'pesantren_id' => $this->pesantren->id,
            'event' => 'modul.diubah',
        ]);
    }

    /**
     * Kalau halaman ini ikut dimatikan, admin yang mematikan seluruh modul
     * kehilangan satu-satunya jalan kembali.
     */
    public function test_halaman_tetap_terjangkau_saat_seluruh_modul_dimatikan(): void
    {
        ModulPengaturan::untuk($this->pesantren->id)->update(
            collect(Modul::cases())->mapWithKeys(fn (Modul $m) => [$m->kolom() => false])->all()
        );

        $this->actingAs($this->admin());

        $this->assertTrue(ModulPengaturanPage::canAccess());
    }
}
