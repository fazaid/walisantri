<?php

namespace Tests\Feature;

use App\Enums\Modul;
use App\Filament\Widgets\AdminKesehatanTrendChart;
use App\Filament\Widgets\AdminNilaiSetoranChart;
use App\Filament\Widgets\AdminSppStatusChart;
use App\Filament\Widgets\AdminStatsOverview;
use App\Filament\Widgets\AdminTrendAmalanChart;
use App\Filament\Widgets\AdminTrendSetoranChart;
use App\Filament\Widgets\PresensiHariIniStat;
use App\Filament\Widgets\UstadzAmalanChart;
use App\Filament\Widgets\UstadzNilaiAkademikChart;
use App\Filament\Widgets\UstadzNilaiSetoranChart;
use App\Filament\Widgets\UstadzProgressHafalanChart;
use App\Filament\Widgets\UstadzStatsOverview;
use App\Filament\Widgets\UstadzTrendSetoranChart;
use App\Models\ModulPengaturan;
use App\Models\Pesantren;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Widget dashboard adalah permukaan yang paling mudah terlewat saat modul dimatikan:
 * ia tidak ada di navigasi, jadi tidak ikut hilang bersama menunya.
 *
 * Yang paling berbahaya bukan grafik kosong, melainkan AdminSppStatusChart —
 * ia menaut ke TagihanSppResource, sehingga membiarkannya tampil saat Keuangan mati
 * menghasilkan kartu yang satu-satunya afordansinya adalah halaman 403.
 */
class ModulWidgetTest extends TestCase
{
    use RefreshDatabase;

    private Pesantren $pesantren;

    /** @var array<class-string, Modul> */
    private array $widgetModul;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pesantren = Pesantren::factory()->create();

        $this->widgetModul = [
            AdminNilaiSetoranChart::class => Modul::Tahfidz,
            AdminTrendSetoranChart::class => Modul::Tahfidz,
            AdminTrendAmalanChart::class => Modul::Kesantrian,
            AdminKesehatanTrendChart::class => Modul::Kesantrian,
            AdminSppStatusChart::class => Modul::Keuangan,
        ];
    }

    private function matikan(Modul ...$modul): void
    {
        ModulPengaturan::untuk($this->pesantren->id)->update(
            collect($modul)->mapWithKeys(fn (Modul $m) => [$m->kolom() => false])->all()
        );
    }

    public function test_widget_admin_mengikuti_tuas_modulnya(): void
    {
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $this->pesantren->id]);
        $this->actingAs($admin);

        foreach ($this->widgetModul as $widget => $modul) {
            $this->assertTrue($widget::canView(), "{$widget} harusnya tampil saat modulnya menyala.");
        }

        $this->matikan(...Modul::cases());

        foreach ($this->widgetModul as $widget => $modul) {
            $this->assertFalse(
                $widget::canView(),
                "{$widget} masih tampil padahal modul {$modul->value} dimatikan."
            );
        }
    }

    public function test_widget_ustadz_mengikuti_tuas_modulnya(): void
    {
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $this->pesantren->id]);
        $this->actingAs($ustadz);

        $this->assertTrue(UstadzNilaiSetoranChart::canView());
        $this->assertTrue(PresensiHariIniStat::canView());

        $this->matikan(Modul::Tahfidz, Modul::Kesantrian, Modul::Akademik, Modul::Presensi);

        foreach ([UstadzNilaiSetoranChart::class, UstadzTrendSetoranChart::class,
            UstadzProgressHafalanChart::class, UstadzAmalanChart::class,
            UstadzNilaiAkademikChart::class, PresensiHariIniStat::class] as $widget) {
            $this->assertFalse($widget::canView(), "{$widget} masih tampil padahal modulnya dimatikan.");
        }
    }

    /**
     * AdminStatsOverview mencampur modul PER STAT — kartu Santri Aktif, Ustadz, Wali,
     * dan Langganan tidak pernah hilang, tapi Santri Sakit & Amalan milik Kesantrian.
     */
    public function test_stat_kesantrian_lenyap_tanpa_menjatuhkan_stat_inti(): void
    {
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $this->pesantren->id]);

        Livewire::actingAs($admin)
            ->test(AdminStatsOverview::class)
            ->assertSee('Santri Sakit Hari Ini')
            ->assertSee('Amalan Minggu Ini');

        $this->matikan(Modul::Kesantrian);

        Livewire::actingAs($admin)
            ->test(AdminStatsOverview::class)
            ->assertDontSee('Santri Sakit Hari Ini')
            ->assertDontSee('Amalan Minggu Ini')
            // Stat inti tidak boleh ikut jatuh.
            ->assertSee('Santri Aktif')
            ->assertSee('Ustadz Terdaftar')
            ->assertSee('Langganan');
    }

    public function test_stat_ustadz_menyusut_mengikuti_modul(): void
    {
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $this->pesantren->id]);

        $this->matikan(Modul::Tahfidz, Modul::Kesantrian);

        Livewire::actingAs($ustadz)
            ->test(UstadzStatsOverview::class)
            ->assertDontSee('Setoran Hari Ini')
            ->assertDontSee('Santri Sakit')
            ->assertSee('Santri Binaan');
    }
}
