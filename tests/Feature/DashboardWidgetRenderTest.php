<?php

namespace Tests\Feature;

use App\Filament\Widgets\AdminKesehatanTrendChart;
use App\Filament\Widgets\AdminNilaiSetoranChart;
use App\Filament\Widgets\AdminSppStatusChart;
use App\Filament\Widgets\AdminStatsOverview;
use App\Filament\Widgets\AdminTrendAmalanChart;
use App\Filament\Widgets\AdminTrendSetoranChart;
use App\Filament\Widgets\ExpiringTenantsWidget;
use App\Filament\Widgets\SuperAdminStatsOverview;
use App\Filament\Widgets\SystemStatsWidget;
use App\Filament\Widgets\TenantListWidget;
use App\Filament\Widgets\UstadzAmalanChart;
use App\Filament\Widgets\UstadzNilaiAkademikChart;
use App\Filament\Widgets\UstadzNilaiSetoranChart;
use App\Filament\Widgets\UstadzProgressHafalanChart;
use App\Filament\Widgets\UstadzStatsOverview;
use App\Filament\Widgets\UstadzTrendSetoranChart;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Widget chart di dashboard di-render malas (lazy) — kesalahan di dalamnya baru
 * muncul saat pengguna men-scroll, bukan saat halaman dibuka, sehingga mudah
 * lolos dari pengujian manual. Tes ini me-render semuanya secara langsung.
 */
class DashboardWidgetRenderTest extends TestCase
{
    use RefreshDatabase;

    private const WIDGET_USTADZ = [
        UstadzStatsOverview::class,
        UstadzTrendSetoranChart::class,
        UstadzAmalanChart::class,
        UstadzNilaiSetoranChart::class,
        UstadzProgressHafalanChart::class,
        UstadzNilaiAkademikChart::class,
    ];

    private const WIDGET_SUPER_ADMIN = [
        SuperAdminStatsOverview::class,
        SystemStatsWidget::class,
        ExpiringTenantsWidget::class,
        TenantListWidget::class,
    ];

    private const WIDGET_ADMIN = [
        AdminStatsOverview::class,
        AdminTrendSetoranChart::class,
        AdminTrendAmalanChart::class,
        AdminNilaiSetoranChart::class,
        AdminKesehatanTrendChart::class,
        AdminSppStatusChart::class,
    ];

    public function test_semua_widget_ustadz_bisa_dirender(): void
    {
        $pesantren = Pesantren::factory()->create();
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);

        // Wajib ada santri bimbingan: beberapa chart keluar lebih awal saat
        // daftar santrinya kosong, sehingga jalur pengambilan data — tempat
        // kesalahan sesungguhnya bersembunyi — tidak pernah dieksekusi.
        Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'pembimbing_ustadz_id' => $ustadz->id,
            'status_aktif' => true,
        ]);

        foreach (self::WIDGET_USTADZ as $widget) {
            Livewire::actingAs($ustadz)
                ->test($widget)
                ->assertOk();
        }
    }

    public function test_semua_widget_admin_pesantren_bisa_dirender(): void
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);

        Santri::factory()->create(['pesantren_id' => $pesantren->id, 'status_aktif' => true]);

        foreach (self::WIDGET_ADMIN as $widget) {
            Livewire::actingAs($admin)
                ->test($widget)
                ->assertOk();
        }
    }

    public function test_semua_widget_super_admin_bisa_dirender(): void
    {
        $superAdmin = User::factory()->superAdmin()->create(['pesantren_id' => null]);

        // Perlu data nyata di setiap status supaya jalur pengambilan data benar-benar
        // dieksekusi, bukan berhenti di hitungan nol.
        Pesantren::factory()->create(['status_berlangganan' => 'active', 'expired_at' => now()->addDays(3)]);
        Pesantren::factory()->create(['status_berlangganan' => 'trial', 'expired_at' => now()->addDays(5)]);
        Pesantren::factory()->create(['status_berlangganan' => 'suspended', 'expired_at' => now()->subDay()]);

        $this->actingAs($superAdmin);

        foreach (self::WIDGET_SUPER_ADMIN as $widget) {
            Livewire::test($widget)->assertOk();
        }
    }

    /**
     * Tiga widget super admin dulu tidak menyetel $sort sehingga jatuh ke default -1
     * (Widget::getSort()) dan terender DI ATAS ringkasan utama yang ber-sort 1.
     */
    public function test_urutan_widget_super_admin_menempatkan_ringkasan_lebih_dulu(): void
    {
        $urutan = collect(self::WIDGET_SUPER_ADMIN)
            ->mapWithKeys(fn (string $widget): array => [$widget => $widget::getSort()]);

        $this->assertSame(
            self::WIDGET_SUPER_ADMIN,
            $urutan->sort()->keys()->all(),
            'Urutan render widget super admin tidak sesuai: ringkasan harus lebih dulu, daftar tenant terakhir.',
        );
    }
}
