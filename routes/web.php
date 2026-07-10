<?php

use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\WaliLoginController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\SlugCheckController;
use App\Http\Controllers\Wali\DashboardController;
use App\Http\Controllers\Wali\InventarisController;
use App\Http\Controllers\Wali\LaporanController;
use App\Http\Controllers\Wali\PengumumanController;
use App\Http\Controllers\Wali\RaporController;
use App\Http\Controllers\Wali\ReportController;
use App\Http\Controllers\Wali\KesehatanStatsController;
use App\Http\Controllers\Wali\MutabaahStatsController;
use App\Http\Controllers\Wali\SppController;
use App\Http\Controllers\Wali\UangSakuController;
use App\Http\Controllers\Wali\TahfidzStatsController;
use App\Models\Order;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

$baseDomain = config('app.base_domain', 'walisantri.com');
$appDomain  = config('app.domain', 'app.walisantri.com');

// Lingkungan dengan satu hostname saja (mis. staging: base_domain == app_domain)
// tidak bisa mendaftarkan '/' dua kali pada domain yang sama — itu bikin route
// kedua menimpa yang pertama di lookup Laravel (nama 'landing' jadi hilang, semua
// view yang panggil route('landing') 500). Gabungkan logikanya jadi satu route.
$sameDomain = $baseDomain === $appDomain;

// =============================================================================
// LANDING — walisantri.com / walisantri.test (§1.6)
// =============================================================================
Route::domain($baseDomain)->group(function () use ($sameDomain) {
    Route::get('/', function () use ($sameDomain) {
        if ($sameDomain && auth()->check()) {
            return match (auth()->user()->role) {
                'wali_santri' => redirect()->route('wali.dashboard'),
                default       => redirect('/admin'),
            };
        }

        return view('landing', [
            'registrationOpen' => PlatformSetting::registrationOpen(),
        ]);
    })->name('landing');

    // Slug check diakses dari halaman register di landing domain
    Route::get('/check-slug/{slug}', SlugCheckController::class)
        ->middleware('throttle:check-slug')
        ->name('check-slug');

    Route::get('/register', [RegisterController::class, 'showForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.submit')->middleware('throttle:register');

    Route::get('/demo', [DemoController::class, 'show'])->name('demo');
    Route::post('/demo', [DemoController::class, 'store'])->name('demo.submit')->middleware('throttle:demo');
});

// =============================================================================
// APP — app.walisantri.com / walisantri.test (login + portal wali + admin)
// Filament panel admin sudah di-handle AdminPanelProvider (domain=APP_DOMAIN)
// =============================================================================
Route::domain($appDomain)->group(function () use ($sameDomain) {

    // --- Root redirect ---
    // Saat base_domain == app_domain (satu hostname, mis. staging), '/' sudah
    // ditangani grup LANDING di atas — jangan didaftarkan lagi di sini.
    if (! $sameDomain) {
        Route::get('/', function () {
            if (! auth()->check()) {
                return redirect()->route('login');
            }
            return match (auth()->user()->role) {
                'wali_santri' => redirect()->route('wali.dashboard'),
                default       => redirect('/admin'),
            };
        });
    }

    // --- Auth login terpusat (§1.3, ?tenant=slug branding) ---
    Route::get('/login', [WaliLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [WaliLoginController::class, 'login'])->name('wali.login.submit');
    Route::post('/logout', [WaliLoginController::class, 'logout'])->middleware('auth')->name('logout');

    // --- Portal Wali Santri (§1.6) ---
    Route::middleware(['auth', 'tenant.resolve', 'saas.lifecycle'])
        ->prefix('wali')
        ->name('wali.')
        ->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
            Route::get('/santri/{santri}', [ReportController::class, 'show'])->name('santri.show');
            Route::get('/santri/{santri}/tahfidz', [TahfidzStatsController::class, 'show'])->name('santri.tahfidz');
            Route::get('/santri/{santri}/kesehatan', [KesehatanStatsController::class, 'show'])->name('santri.kesehatan');
            Route::get('/santri/{santri}/mutabaah', [MutabaahStatsController::class, 'show'])->name('santri.mutabaah');
            Route::get('/santri/{santri}/inventaris', [InventarisController::class, 'show'])->name('santri.inventaris');
            Route::get('/pengumuman', [PengumumanController::class, 'index'])->name('pengumuman');
            Route::get('/rapor', [RaporController::class, 'index'])->name('rapor');
            Route::get('/laporan/pdf', [LaporanController::class, 'exportPdf'])->name('laporan.pdf');
            Route::get('/spp', [SppController::class, 'index'])->name('spp');
            Route::post('/spp/{tagihan}/konfirmasi', [SppController::class, 'konfirmasi'])->name('spp.konfirmasi');
            Route::get('/uang-saku', [UangSakuController::class, 'index'])->name('uang-saku');
            Route::get('/uang-saku/{santri}', [UangSakuController::class, 'show'])->name('uang-saku.show');
        });

    // --- Magic Link — /report/{uuid} (§4.3) ---
    Route::get('/report/{uuid}', [ReportController::class, 'showByUuid'])
        ->middleware('magic.token')
        ->name('wali.magic.report');

    // --- Bukti transfer order — hanya super_admin ---
    Route::get('/orders/{order}/bukti-transfer', function (Order $order) {
        abort_unless(Auth::check() && Auth::user()->role === 'super_admin', 403);
        abort_unless($order->invoice?->bukti_transfer_path, 404);

        $path = $order->invoice->bukti_transfer_path;
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    })->middleware('auth')->name('orders.bukti-transfer');

    // --- Export Excel (admin panel) ---
    Route::middleware(['auth', 'tenant.resolve'])
        ->prefix('admin-export')
        ->name('admin.export.')
        ->group(function () {
            Route::get('santri', [ExportController::class, 'santri'])->name('santri');
            Route::get('mutabaah', [ExportController::class, 'mutabaah'])->name('mutabaah');
            Route::get('rekam-medis', [ExportController::class, 'rekamMedis'])->name('rekam-medis');
        });
});

// =============================================================================
// PROFIL PUBLIK — {slug}.walisantri.com (§1.4)
// PublicTenantResolver cocokkan hostname ke tenant_domains → set pesantren
// =============================================================================
Route::domain('{slug}.' . $baseDomain)
    ->middleware('public.tenant')
    ->group(function () {
        Route::get('/', [PublicProfileController::class, 'index'])->name('public.profile');
        Route::get('/kegiatan', [PublicProfileController::class, 'kegiatan'])->name('public.kegiatan');
        Route::get('/artikel', [PublicProfileController::class, 'artikel'])->name('public.artikel');
    });
