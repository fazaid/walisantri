<?php

namespace Tests\Feature;

use App\Filament\Widgets\ExpiringTenantsWidget;
use App\Filament\Widgets\TenantListWidget;
use App\Jobs\CheckExpiredTenants;
use App\Models\MasterPengumuman;
use App\Models\Pesantren;
use App\Models\Presensi;
use App\Models\Santri;
use App\Models\TagihanSpp;
use App\Models\User;
use App\Support\SandboxDemo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Sandbox publik demo.walisantri.com — tenant contoh yang tautan portal walinya
 * dipublikasikan di landing.
 */
class SandboxDemoTest extends TestCase
{
    use RefreshDatabase;

    private function segarkan(): void
    {
        Queue::fake();
        $this->artisan('sandbox:segarkan')->assertSuccessful();
        Cache::forget(SandboxDemo::CACHE_KEY);
    }

    private function cobaUrl(): string
    {
        return 'http://'.config('app.base_domain').'/coba';
    }

    public function test_perintah_membuat_tenant_sandbox_yang_tidak_pernah_kedaluwarsa(): void
    {
        $this->segarkan();

        $pesantren = Pesantren::where('slug', SandboxDemo::SLUG)->firstOrFail();

        $this->assertTrue($pesantren->is_demo);
        $this->assertSame('active', $pesantren->status_berlangganan);
        // expired_at null = tidak pernah disentuh SaaSLifecycleLock maupun
        // ketiga job kedaluwarsa (semuanya butuh whereNotNull).
        $this->assertNull($pesantren->expired_at);
        $this->assertNotEmpty($pesantren->profil['deskripsi'] ?? null);
    }

    public function test_subdomain_publik_terdaftar_supaya_tidak_404(): void
    {
        $this->segarkan();

        $pesantren = Pesantren::where('slug', SandboxDemo::SLUG)->firstOrFail();

        $this->assertDatabaseHas('tenant_domains', [
            'pesantren_id' => $pesantren->id,
            'hostname' => SandboxDemo::SLUG.'.'.config('app.base_domain'),
        ]);
    }

    /**
     * Jaminan paling penting di berkas ini. `santri.uuid` adalah token magic
     * link; kalau penyegaran mingguan membuat ulang santri, setiap tautan demo
     * yang sudah dibagikan akan mati diam-diam.
     */
    public function test_penyegaran_ulang_tidak_menggandakan_baris_dan_tidak_mengubah_uuid(): void
    {
        $this->segarkan();

        $pesantren = Pesantren::where('slug', SandboxDemo::SLUG)->firstOrFail();
        $uuidAwal = SandboxDemo::santriContoh($pesantren)->uuid;
        $jumlahAwal = [
            'pesantren' => Pesantren::where('slug', SandboxDemo::SLUG)->count(),
            'santri' => Santri::withoutGlobalScope('pesantren')->where('pesantren_id', $pesantren->id)->count(),
            'presensi' => Presensi::where('pesantren_id', $pesantren->id)->count(),
            'tagihan' => TagihanSpp::where('pesantren_id', $pesantren->id)->count(),
            'pengumuman' => MasterPengumuman::where('pesantren_id', $pesantren->id)->count(),
        ];

        $this->segarkan();

        $this->assertSame($uuidAwal, SandboxDemo::santriContoh($pesantren->fresh())->uuid);
        $this->assertSame($jumlahAwal, [
            'pesantren' => Pesantren::where('slug', SandboxDemo::SLUG)->count(),
            'santri' => Santri::withoutGlobalScope('pesantren')->where('pesantren_id', $pesantren->id)->count(),
            'presensi' => Presensi::where('pesantren_id', $pesantren->id)->count(),
            'tagihan' => TagihanSpp::where('pesantren_id', $pesantren->id)->count(),
            'pengumuman' => MasterPengumuman::where('pesantren_id', $pesantren->id)->count(),
        ]);
    }

    public function test_dry_run_tidak_menulis_apa_pun(): void
    {
        $this->artisan('sandbox:segarkan', ['--dry-run' => true])->assertSuccessful();

        $this->assertDatabaseMissing('pesantrens', ['slug' => SandboxDemo::SLUG]);
    }

    public function test_coba_mengalihkan_ke_portal_wali_contoh(): void
    {
        $this->segarkan();

        $pesantren = Pesantren::where('slug', SandboxDemo::SLUG)->firstOrFail();
        $santri = SandboxDemo::santriContoh($pesantren);

        $this->get($this->cobaUrl())->assertRedirect($santri->linkWali());
    }

    public function test_coba_404_saat_sandbox_belum_ada(): void
    {
        Cache::forget(SandboxDemo::CACHE_KEY);

        $this->get($this->cobaUrl())->assertNotFound();
    }

    public function test_tenant_sandbox_tidak_terhitung_sebagai_pelanggan(): void
    {
        $this->segarkan();
        Pesantren::factory()->aktif()->create();

        $this->assertSame(1, Pesantren::pelanggan()->count());
        $this->assertSame(2, Pesantren::count());
    }

    public function test_tenant_sandbox_tidak_muncul_di_widget_super_admin(): void
    {
        $this->segarkan();

        $superAdmin = User::factory()->superAdmin()->create();
        $sandbox = Pesantren::where('slug', SandboxDemo::SLUG)->firstOrFail();

        $this->actingAs($superAdmin);

        Livewire::test(TenantListWidget::class)->assertCanNotSeeTableRecords([$sandbox]);
        Livewire::test(ExpiringTenantsWidget::class)->assertCanNotSeeTableRecords([$sandbox]);
    }

    public function test_job_kedaluwarsa_tidak_menyentuh_tenant_sandbox(): void
    {
        $this->segarkan();

        (new CheckExpiredTenants)->handle();

        $this->assertSame(
            'active',
            Pesantren::where('slug', SandboxDemo::SLUG)->value('status_berlangganan')
        );
    }
}
