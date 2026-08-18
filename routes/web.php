<?php

use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\SerahTerimaSesiController;
use App\Http\Controllers\Auth\VerifikasiEmailController;
use App\Http\Controllers\Auth\WaliLoginController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\HargaController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PanduanController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\SlugCheckController;
use App\Http\Controllers\Wali\DashboardController;
use App\Http\Controllers\Wali\InventarisController;
use App\Http\Controllers\Wali\IzinController;
use App\Http\Controllers\Wali\KesehatanStatsController;
use App\Http\Controllers\Wali\LaporanController;
use App\Http\Controllers\Wali\MutabaahStatsController;
use App\Http\Controllers\Wali\PengumumanController;
use App\Http\Controllers\Wali\PresensiController;
use App\Http\Controllers\Wali\RaporController;
use App\Http\Controllers\Wali\ReportController;
use App\Http\Controllers\Wali\SppController;
use App\Http\Controllers\Wali\TahfidzStatsController;
use App\Http\Controllers\Wali\UangSakuController;
use App\Models\Order;
use App\Models\Santri;
use App\Support\SandboxDemo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

$baseDomain = config('app.base_domain', 'walisantri.com');
$appDomain = config('app.domain', 'app.walisantri.com');

// Lingkungan dengan satu hostname saja (mis. staging: base_domain == app_domain)
// tidak bisa mendaftarkan '/' dua kali pada domain yang sama — itu bikin route
// kedua menimpa yang pertama di lookup Laravel (nama 'landing' jadi hilang, semua
// view yang panggil route('landing') 500). Gabungkan logikanya jadi satu route.
$sameDomain = $baseDomain === $appDomain;

// =============================================================================
// LANDING — walisantri.com / walisantri.test (§1.6)
// =============================================================================
Route::domain($baseDomain)->group(function () use ($sameDomain) {
    Route::get('/', fn (LandingController $landing) => $landing($sameDomain))->name('landing');

    // Sandbox publik: mengalihkan ke portal wali contoh. UUID-nya diturunkan
    // saat request, bukan di-hardcode, jadi penyegaran data mingguan tidak
    // pernah mematikan tautan ini.
    Route::get('/coba', function () {
        $url = SandboxDemo::waliUrl();

        abort_if($url === null, 404);

        return redirect()->away($url);
    })->name('sandbox.coba');

    // Slug check diakses dari halaman register di landing domain
    Route::get('/check-slug/{slug}', SlugCheckController::class)
        ->middleware('throttle:check-slug')
        ->name('check-slug');

    Route::get('/register', [RegisterController::class, 'showForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.submit')->middleware('throttle:register');

    Route::get('/demo', [DemoController::class, 'show'])->name('demo');
    Route::post('/demo', [DemoController::class, 'store'])->name('demo.submit')->middleware('throttle:demo');

    // Paket harga (§1.6). Rumah kanoniknya di sini, bukan seksi anchor di landing.
    // Cukup didaftarkan di domain ini: berbeda dari '/' dan '/panduan', pathnya tidak
    // bertabrakan saat base_domain == app_domain.
    Route::get('/harga', HargaController::class)->name('harga');

    // Panduan juga tersedia di domain utama (tanpa "app.") — dilewati saat
    // $sameDomain karena rute 'panduan' di grup APP di bawah sudah menjajaki
    // domain yang sama; mendaftarkan path yang sama dua kali di domain
    // identik membuat definisi kedua menimpa yang pertama (lihat catatan
    // $sameDomain untuk rute '/' di atas).
    if (! $sameDomain) {
        Route::get('/panduan', PanduanController::class);
    }
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
                // Lihat catatan di WaliLoginController::redirectAfterLogin().
                'wali_santri' => redirect()->away(auth()->user()->urlPortalWali()),
                default => redirect('/admin'),
            };
        });
    }

    // --- Auth login terpusat (§1.3, ?tenant=slug branding) ---
    // Pintu staf. Sejak §1.8 Fase 1, wali masuk lewat host pesantrennya sendiri —
    // wali yang telanjur mendarat di sini dialihkan ke sana oleh controller, sebab
    // sesi yang dibuat di host ini tidak akan terbawa ke host tenant (cookie
    // ber-scope host).
    Route::get('/login', [WaliLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [WaliLoginController::class, 'login'])->name('login.submit');
    Route::post('/logout', [WaliLoginController::class, 'logout'])->middleware('auth')->name('logout');

    // --- Serah-terima sesi lintas host (§1.8 Fase 1) ---
    // Dicetak HANYA oleh RegisterController::store(): /register hidup di apex,
    // panel di host ini, dan cookie sesi ber-scope host — tanpa rute ini pendaftar
    // mendarat di panel sebagai tamu. Sekali pakai + kedaluwarsa 5 menit.
    Route::get('/masuk-otomatis/{token}', SerahTerimaSesiController::class)
        ->middleware(['signed', 'throttle:magic-link'])
        ->name('auth.serah-terima');

    // --- Reset kata sandi lewat email (§9.1) — staf saja; wali santri passwordless ---
    Route::get('/lupa-password', [ResetPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/lupa-password', [ResetPasswordController::class, 'sendResetLink'])
        ->middleware('throttle:password-reset')
        ->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])
        ->middleware('throttle:password-reset')
        ->name('password.update');

    // --- Verifikasi email (§12.2) — lunak, tidak memblokir akses apa pun ---
    // Tautan verifikasi tidak mewajibkan sesi: lumrah dibuka di perangkat lain,
    // dan tanda tangan URL sudah jadi buktinya.
    Route::get('/verifikasi-email/{id}/{hash}', [VerifikasiEmailController::class, 'verify'])
        ->middleware(['signed', 'throttle:verifikasi-email'])
        ->name('verification.verify');
    Route::post('/verifikasi-email/kirim-ulang', [VerifikasiEmailController::class, 'resend'])
        ->middleware(['auth', 'throttle:verifikasi-email'])
        ->name('verification.send');

    // --- Panduan penggunaan untuk Admin Pesantren & Ustadz — statis, tanpa login ---
    Route::get('/panduan', PanduanController::class)->name('panduan');

    // --- Pintu kanonik magic link (§1.8 Fase 1) ---
    //
    // Rute ini TIDAK PERNAH dihapus, dan tugasnya HANYA mengalihkan. Magic link
    // tidak punya kedaluwarsa (§4.3): tautan yang tersimpan di HP wali bisa dipakai
    // bertahun-tahun, jadi perpindahan portal ke host tenant harus aditif.
    //
    // Sengaja TANPA middleware magic.token: yang mengautentikasi adalah host tenant.
    // Kalau login terjadi di sini, sesinya lahir di host yang salah dan — karena
    // cookie ber-scope host — tidak akan terbawa ke tujuan.
    //
    // Tujuannya dihitung dari tenant SANTRI-nya, bukan dari host yang diketuk,
    // sehingga penggantian slug tidak pernah mematikan tautan lama (§1.4).
    Route::get('/report/{uuid}', function (string $uuid, Request $request) {
        $santri = Santri::withoutGlobalScope('pesantren')->where('uuid', $uuid)->first();

        abort_if($santri === null, 404);

        $tujuan = $santri->linkWali();

        // Tenant tanpa baris tenant_domains membuat linkWali() jatuh kembali ke host
        // platform — persis host ini. Mengalihkannya berarti loop tak berujung, jadi
        // lebih baik gagal terang-terangan. Di production semua tenant punya baris
        // domainnya (diperiksa saat rilis §1.8 Fase 1); ini pagar untuk data rusak.
        abort_if(parse_url($tujuan, PHP_URL_HOST) === $request->getHost(), 404);

        return redirect()->away($tujuan);
    })->middleware('throttle:magic-link')->name('magic.kanonik');

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
            Route::get('presensi', [ExportController::class, 'presensi'])->name('presensi');
        });

    // --- Preview portal wali dari admin panel (tanpa Auth::login, sesi admin tetap utuh) ---
    Route::middleware(['auth', 'tenant.resolve'])
        ->prefix('admin-preview')
        ->name('admin.preview.')
        ->group(function () {
            Route::get('/wali/{santri}', [ReportController::class, 'previewAsAdmin'])->name('wali');
        });
});

// =============================================================================
// PROFIL PUBLIK — {slug}.walisantri.com (§1.4)
// PublicTenantResolver cocokkan hostname ke tenant_domains → set pesantren
// =============================================================================
Route::domain('{slug}.'.$baseDomain)
    ->middleware('public.tenant')
    ->group(function () {
        // Profil publik sengaja TANPA pagar tenant.host: halaman ini boleh dibaca
        // siapa saja, termasuk pengguna yang sedang login di pesantren lain.
        Route::get('/', [PublicProfileController::class, 'index'])->name('public.profile');
        Route::get('/kegiatan', [PublicProfileController::class, 'kegiatan'])->name('public.kegiatan');
        Route::get('/artikel', [PublicProfileController::class, 'artikel'])->name('public.artikel');
    });

// =============================================================================
// PERMUKAAN WALI DI HOST TENANT — {slug}.walisantri.com (§1.8 Fase 1)
//
// Login, portal, dan magic link tinggal satu host dengan profil pesantren, supaya
// wali tidak pernah melihat merek platform di titik sentuh hariannya.
//
// ⚠️ URUTAN PENDAFTARAN ITU PAGARNYA. Pola '{slug}.walisantri.com' juga cocok
// dengan 'app.walisantri.com' — grup APP di atas didaftarkan LEBIH DULU sehingga
// ia yang menang. Jangan pernah memindahkan blok ini ke atas grup itu; ada tes
// yang menjaganya (TenantHostTest).
// =============================================================================
Route::domain('{slug}.'.$baseDomain)
    ->middleware(['public.tenant', 'tenant.host'])
    ->group(function () {
        // Serah-terima sesi dari pintu platform ke sini (§1.8 Fase 1) — wali yang
        // login di app.walisantri.com diantar ke portalnya tanpa mengetik ulang.
        Route::get('/masuk-otomatis/{token}', SerahTerimaSesiController::class)
            ->middleware(['signed', 'throttle:magic-link'])
            ->name('wali.serah-terima');

        Route::get('/login', [WaliLoginController::class, 'showLoginForm'])->name('wali.login');
        Route::post('/login', [WaliLoginController::class, 'login'])->name('wali.login.submit');
        Route::post('/logout', [WaliLoginController::class, 'logout'])->middleware('auth')->name('wali.logout');

        // Magic Link (§4.3) — handler sesungguhnya; pintu kanonik di app host
        // hanya mengalihkan ke sini.
        Route::get('/report/{uuid}', [ReportController::class, 'showByUuid'])
            ->middleware(['magic.token', 'throttle:magic-link'])
            ->name('wali.magic.report');

        // --- Portal Wali Santri (§1.6) ---
        Route::middleware(['auth', 'magic.block', 'tenant.resolve', 'saas.lifecycle'])
            ->prefix('wali')
            ->name('wali.')
            ->group(function () {
                Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
                Route::get('/santri/{santri}', [ReportController::class, 'show'])->name('santri.show');
                Route::get('/santri/{santri}/tahfidz', [TahfidzStatsController::class, 'show'])->name('santri.tahfidz');
                Route::get('/santri/{santri}/kesehatan', [KesehatanStatsController::class, 'show'])->name('santri.kesehatan');
                Route::get('/santri/{santri}/mutabaah', [MutabaahStatsController::class, 'show'])->name('santri.mutabaah');
                Route::get('/santri/{santri}/inventaris', [InventarisController::class, 'show'])->name('santri.inventaris');
                Route::get('/santri/{santri}/presensi', [PresensiController::class, 'show'])->name('santri.presensi');
                Route::get('/pengumuman', [PengumumanController::class, 'index'])->name('pengumuman');
                Route::get('/rapor', [RaporController::class, 'index'])->name('rapor');
                Route::get('/laporan/pdf', [LaporanController::class, 'exportPdf'])->name('laporan.pdf');
                Route::get('/spp', [SppController::class, 'index'])->name('spp');
                Route::post('/spp/{tagihan}/konfirmasi', [SppController::class, 'konfirmasi'])->name('spp.konfirmasi');
                Route::get('/uang-saku', [UangSakuController::class, 'index'])->name('uang-saku');
                Route::get('/uang-saku/{santri}', [UangSakuController::class, 'show'])->name('uang-saku.show');
                Route::get('/izin', [IzinController::class, 'index'])->name('izin');
                Route::post('/izin', [IzinController::class, 'store'])->name('izin.store');
                // Lampiran di disk 'local' — tidak punya URL publik, jadi disajikan
                // lewat rute terotorisasi (pola orders.bukti-transfer).
                Route::get('/izin/{izin}/lampiran', [IzinController::class, 'lampiran'])->name('izin.lampiran');
            });
    });
