<?php

namespace Tests\Feature;

use App\Filament\Pages\BillingPage;
use App\Filament\Pages\UpgradePage;
use App\Models\Pesantren;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SaaSLifecycleLockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Minimal route protected by saas.lifecycle only (not in web group,
        // so SaaSLifecycleLock runs exactly once per request).
        Route::match(['GET', 'POST'], '/test-saas', fn() => response('ok', 200))
            ->middleware(['auth', 'saas.lifecycle']);
    }

    private function makePesantren(array $override = []): Pesantren
    {
        return Pesantren::create(array_merge([
            'nama_pesantren'      => 'Pesantren Lifecycle',
            'slug'                => 'pesantren-lifecycle-' . uniqid(),
            'paket_langganan'     => 'rintisan',
            'max_santri_kuota'    => 100,
            'status_berlangganan' => 'active',
            'expired_at'          => now()->addYear(),
        ], $override));
    }

    private function makeUser(Pesantren $pesantren, string $role): User
    {
        static $counter = 0;
        $counter++;

        return User::create([
            'pesantren_id' => $pesantren->id,
            'name'         => "{$role} {$counter}",
            'email'        => strtolower(str_replace('_', '', $role)) . ".{$counter}@saas.test",
            'password'     => bcrypt('password'),
            'role'         => $role,
        ]);
    }

    // ─── Suspended ─────────────────────────────────────────────────────────────

    public function test_akses_diblokir_saat_status_suspended_untuk_admin(): void
    {
        $pesantren = $this->makePesantren(['status_berlangganan' => 'suspended']);
        $admin     = $this->makeUser($pesantren, 'admin_pesantren');

        // Admin pesantren → redirectBilling → JSON 402
        $this->actingAs($admin)
            ->getJson('/test-saas')
            ->assertStatus(402);
    }

    public function test_akses_diblokir_saat_status_suspended_untuk_wali_santri(): void
    {
        $pesantren = $this->makePesantren(['status_berlangganan' => 'suspended']);
        $wali      = $this->makeUser($pesantren, 'wali_santri');

        // Wali santri → lockResponse 423 (Locked)
        $this->actingAs($wali)
            ->getJson('/test-saas')
            ->assertStatus(423);
    }

    public function test_akses_diblokir_saat_status_suspended_untuk_ustadz(): void
    {
        $pesantren = $this->makePesantren(['status_berlangganan' => 'suspended']);
        $ustadz    = $this->makeUser($pesantren, 'ustadz');

        // Ustadz diperlakukan sama seperti admin_pesantren → redirectBilling → JSON 402
        $this->actingAs($ustadz)
            ->getJson('/test-saas')
            ->assertStatus(402);
    }

    // ─── Grace period (expired < 7 days) ───────────────────────────────────────

    public function test_wali_santri_mendapat_grace_period_7_hari_saat_expired(): void
    {
        // Expired 3 days ago → still within the 7-day grace window.
        $pesantren = $this->makePesantren([
            'status_berlangganan' => 'expired',
            'expired_at'          => now()->subDays(3),
        ]);
        $wali = $this->makeUser($pesantren, 'wali_santri');

        // GET requests must pass through during grace period.
        $this->actingAs($wali)
            ->getJson('/test-saas')
            ->assertStatus(200);
    }

    public function test_akses_non_get_diblokir_selama_grace_period(): void
    {
        $pesantren = $this->makePesantren([
            'status_berlangganan' => 'expired',
            'expired_at'          => now()->subDays(3),
        ]);
        $wali = $this->makeUser($pesantren, 'wali_santri');

        // POST (mutating) request must be aborted with 403 during grace period.
        $this->actingAs($wali)
            ->postJson('/test-saas')
            ->assertStatus(403);
    }

    /**
     * Regresi: sebelum fix, diffInDays() tanpa $absolute=true di Carbon 3
     * mengembalikan nilai negatif untuk expired_at di masa lalu — bikin kondisi
     * "$daysSinceExpired > WALI_GRACE_DAYS" tidak pernah true, jadi wali santri
     * TIDAK PERNAH terkunci walau sudah lewat 7 hari masa tenggang. Semua test
     * "grace period" lain di file ini cuma pakai subDays(3) (masih dalam window
     * 7 hari) sehingga tidak pernah menyentuh baris ini dengan cara yang
     * membedakan hasil benar vs salah.
     */
    public function test_wali_santri_terkunci_setelah_lewat_grace_period_7_hari(): void
    {
        // Expired 10 hari lalu → sudah lewat grace period 7 hari.
        $pesantren = $this->makePesantren([
            'status_berlangganan' => 'expired',
            'expired_at'          => now()->subDays(10),
        ]);
        $wali = $this->makeUser($pesantren, 'wali_santri');

        $this->actingAs($wali)
            ->getJson('/test-saas')
            ->assertStatus(423);
    }

    // ─── Ustadz diarahkan ke billing (bukan 403 mentah) ────────────────────────

    public function test_ustadz_expired_diarahkan_ke_billing(): void
    {
        $pesantren = $this->makePesantren([
            'status_berlangganan' => 'expired',
            'expired_at'          => now()->subDays(3),
        ]);
        $ustadz = $this->makeUser($pesantren, 'ustadz');

        // Sama seperti admin_pesantren: request browser biasa (non-JSON) di-redirect
        // ke BillingPage, bukan dikunci dengan status error mentah.
        $this->actingAs($ustadz)
            ->get('/test-saas')
            ->assertRedirect(BillingPage::getUrl());
    }

    // ─── Response non-JSON (browser biasa) untuk wali santri terkunci ──────────

    public function test_wali_santri_terkunci_melihat_pesan_bukan_halaman_kosong(): void
    {
        // Sebelum resources/views/errors/423.blade.php dibuat, abort(423, $message)
        // untuk request non-JSON jatuh ke convertExceptionToResponse() tanpa view
        // khusus (Laravel core hanya sediakan 401/402/403/404/419/429/500/503),
        // sehingga wali santri melihat halaman kosong/generik alih-alih pesan
        // "Masa tenggang akses wali santri telah berakhir." yang sudah ditulis
        // di SaaSLifecycleLock::lockResponse().
        $pesantren = $this->makePesantren([
            'status_berlangganan' => 'expired',
            'expired_at'          => now()->subDays(10),
        ]);
        $wali = $this->makeUser($pesantren, 'wali_santri');

        $this->actingAs($wali)
            ->get('/test-saas')
            ->assertStatus(423)
            ->assertSee('Masa tenggang akses wali santri telah berakhir.');
    }

    // ─── Billing whitelist (route asli Filament, bukan /test-saas) ────────────

    public function test_admin_expired_bisa_akses_halaman_billing_asli(): void
    {
        $pesantren = $this->makePesantren([
            'status_berlangganan' => 'expired',
            'expired_at'          => now()->subDays(3),
        ]);
        $admin = $this->makeUser($pesantren, 'admin_pesantren');

        // Sebelum fix: whitelist path-string tidak cocok dengan URL cluster
        // "admin/pengaturan/billing-page" → infinite redirect loop.
        $this->actingAs($admin)
            ->get(BillingPage::getUrl())
            ->assertOk();
    }

    public function test_admin_expired_bisa_akses_halaman_upgrade_asli(): void
    {
        $pesantren = $this->makePesantren([
            'status_berlangganan' => 'expired',
            'expired_at'          => now()->subDays(3),
        ]);
        $admin = $this->makeUser($pesantren, 'admin_pesantren');

        $this->actingAs($admin)
            ->get(UpgradePage::getUrl())
            ->assertOk();
    }
}
