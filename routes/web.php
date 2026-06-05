<?php

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\WaliLoginController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\SlugCheckController;
use App\Http\Controllers\Wali\DashboardController;
use App\Http\Controllers\Wali\LaporanController;
use App\Http\Controllers\Wali\PengumumanController;
use App\Http\Controllers\Wali\RaporController;
use App\Http\Controllers\Wali\ReportController;
use App\Http\Controllers\Wali\KesehatanStatsController;
use App\Http\Controllers\Wali\MutabaahStatsController;
use App\Http\Controllers\Wali\SppController;
use App\Http\Controllers\Wali\TahfidzStatsController;
use Illuminate\Support\Facades\Route;

$baseDomain = config('app.base_domain', 'walisantri.com');
$appDomain  = config('app.domain', 'app.walisantri.com');

// =============================================================================
// LANDING — walisantri.com / walisantri.test (§1.6)
// =============================================================================
Route::domain($baseDomain)->group(function () {
    Route::get('/', function () {
        return view('landing');
    })->name('landing');

    // Slug check diakses dari halaman register di landing domain
    Route::get('/check-slug/{slug}', SlugCheckController::class)
        ->middleware('throttle:check-slug')
        ->name('check-slug');

    Route::get('/register', [RegisterController::class, 'showForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.submit');

    Route::get('/demo', [DemoController::class, 'show'])->name('demo');
    Route::post('/demo', [DemoController::class, 'store'])->name('demo.submit');
});

// =============================================================================
// APP — app.walisantri.com / walisantri.test (login + portal wali + admin)
// Filament panel admin sudah di-handle AdminPanelProvider (domain=APP_DOMAIN)
// =============================================================================
Route::domain($appDomain)->group(function () {

    // --- Root redirect ---
    Route::get('/', function () {
        if (! auth()->check()) {
            return redirect()->route('login');
        }
        return match (auth()->user()->role) {
            'wali_santri' => redirect()->route('wali.dashboard'),
            default       => redirect('/admin'),
        };
    });

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
            Route::get('/pengumuman', [PengumumanController::class, 'index'])->name('pengumuman');
            Route::get('/rapor', [RaporController::class, 'index'])->name('rapor');
            Route::get('/laporan/pdf', [LaporanController::class, 'exportPdf'])->name('laporan.pdf');
            Route::get('/spp', [SppController::class, 'index'])->name('spp');
        });

    // --- Magic Link — /report/{uuid} (§4.3) ---
    Route::get('/report/{uuid}', [ReportController::class, 'showByUuid'])
        ->middleware('magic.token')
        ->name('wali.magic.report');

    // --- Logout wali (magic link maupun login biasa) ---
    Route::post('/wali/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('login');
    })->middleware('auth')->name('wali.logout');
});

// =============================================================================
// PROFIL PUBLIK — {slug}.walisantri.com (§1.4)
// PublicTenantResolver cocokkan hostname ke tenant_domains → set pesantren
// =============================================================================
Route::domain('{slug}.' . $baseDomain)
    ->middleware('public.tenant')
    ->group(function () {
        Route::get('/', [PublicProfileController::class, 'index'])->name('public.profile');
        Route::get('/pengumuman', [PublicProfileController::class, 'pengumuman'])->name('public.pengumuman');
    });
