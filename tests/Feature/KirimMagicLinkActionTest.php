<?php

namespace Tests\Feature;

use App\Filament\Resources\Santris\Pages\ListSantris;
use App\Filament\Resources\Santris\Pages\ViewSantri;
use App\Filament\Resources\Santris\SantriResource;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KirimMagicLinkActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_tombol_link_wali_nonaktif_kalau_santri_belum_punya_wali(): void
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->create(['role' => 'admin_pesantren', 'pesantren_id' => $pesantren->id]);
        $santri = Santri::factory()->create(['pesantren_id' => $pesantren->id, 'wali_santri_id' => null]);

        $this->actingAs($admin);

        Livewire::test(ViewSantri::class, ['record' => $santri->getRouteKey()])
            ->assertActionDisabled('kirim_magic_link');
    }

    public function test_tombol_link_wali_aktif_kalau_santri_sudah_punya_wali(): void
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->create(['role' => 'admin_pesantren', 'pesantren_id' => $pesantren->id]);
        $wali = User::factory()->waliSantri()->create(['pesantren_id' => $pesantren->id]);
        $santri = Santri::factory()->create(['pesantren_id' => $pesantren->id, 'wali_santri_id' => $wali->id]);

        $this->actingAs($admin);

        Livewire::test(ViewSantri::class, ['record' => $santri->getRouteKey()])
            ->assertActionEnabled('kirim_magic_link');
    }

    public function test_link_wali_dibangun_dari_domain_aplikasi_dan_uuid(): void
    {
        config(['app.domain' => 'app.walisantri.test']);

        $pesantren = Pesantren::factory()->create();
        $santri = Santri::factory()->create(['pesantren_id' => $pesantren->id]);

        $this->assertSame(
            "http://app.walisantri.test/report/{$santri->uuid}",
            $santri->linkWali(),
        );
    }

    public function test_kolom_link_wali_menandai_santri_yang_belum_punya_wali(): void
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->create(['role' => 'admin_pesantren', 'pesantren_id' => $pesantren->id]);
        $wali = User::factory()->waliSantri()->create(['pesantren_id' => $pesantren->id]);
        $punyaWali = Santri::factory()->create(['pesantren_id' => $pesantren->id, 'wali_santri_id' => $wali->id]);
        $tanpaWali = Santri::factory()->create(['pesantren_id' => $pesantren->id, 'wali_santri_id' => null]);

        $this->actingAs($admin);

        Livewire::test(ListSantris::class)
            ->assertCanRenderTableColumn('link_wali')
            ->assertTableColumnStateSet('link_wali', 'Salin Link', $punyaWali)
            ->assertTableColumnStateSet('link_wali', '— belum ada wali —', $tanpaWali);
    }

    public function test_yang_tersalin_adalah_url_penuh_bukan_teks_badge(): void
    {
        $pesantren = Pesantren::factory()->create();
        $wali = User::factory()->waliSantri()->create(['pesantren_id' => $pesantren->id]);
        $santri = Santri::factory()->create(['pesantren_id' => $pesantren->id, 'wali_santri_id' => $wali->id]);

        $admin = User::factory()->create(['role' => 'admin_pesantren', 'pesantren_id' => $pesantren->id]);
        $this->actingAs($admin);

        $kolom = Livewire::test(ListSantris::class)
            ->instance()
            ->getTable()
            ->getColumn('link_wali')
            ->record($santri);

        // Badge-nya pendek, tapi isi clipboard harus URL penuh.
        $this->assertSame('Salin Link', $kolom->getState());
        $this->assertSame($santri->linkWali(), $kolom->getCopyableState($kolom->getState()));
    }

    public function test_fallback_clipboard_ikut_dirender_di_panel_admin(): void
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->create(['role' => 'admin_pesantren', 'pesantren_id' => $pesantren->id]);

        // Tanpa polyfill ini, copyable() gagal diam-diam di luar secure context.
        $this->actingAs($admin)
            ->get(SantriResource::getUrl('index'))
            ->assertOk()
            ->assertSee('window.isSecureContext', escape: false)
            ->assertSee("document.execCommand('copy')", escape: false);
    }

    public function test_jenis_kelamin_dan_pembimbing_tersembunyi_secara_default(): void
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->create(['role' => 'admin_pesantren', 'pesantren_id' => $pesantren->id]);
        Santri::factory()->create(['pesantren_id' => $pesantren->id]);

        $this->actingAs($admin);

        // assertTableColumnHidden() mengukur ->hidden(), bukan status toggle,
        // jadi status kolom diperiksa langsung.
        $tabel = Livewire::test(ListSantris::class)->instance()->getTable();

        $this->assertTrue($tabel->getColumn('jenis_kelamin')->isToggledHiddenByDefault());
        $this->assertTrue($tabel->getColumn('pembimbing.name')->isToggledHiddenByDefault());
        $this->assertFalse($tabel->getColumn('link_wali')->isToggledHiddenByDefault());
        $this->assertFalse($tabel->getColumn('wali.name')->isToggledHiddenByDefault());
    }
}
