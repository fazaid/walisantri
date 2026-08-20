<?php

use App\Http\Middleware\BlockMagicLinkSession;
use App\Http\Middleware\CheckTenantQuota;
use App\Http\Middleware\PastikanModulAktif;
use App\Http\Middleware\PublicTenantResolver;
use App\Http\Middleware\ResolveTenantFromAccount;
use App\Http\Middleware\SaaSLifecycleLock;
use App\Http\Middleware\TenantHost;
use App\Http\Middleware\VerifyMagicToken;
use App\Models\TenantDomain;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Daftarkan alias untuk dipanggil via #[Middleware] atau route middleware
        $middleware->alias([
            'tenant.quota' => CheckTenantQuota::class,
            'saas.lifecycle' => SaaSLifecycleLock::class,
            'magic.token' => VerifyMagicToken::class,
            'magic.block' => BlockMagicLinkSession::class,
            'public.tenant' => PublicTenantResolver::class,
            // Pagar host tenant §1.8 Fase 1 — dipasang bersama public.tenant,
            // selalu SESUDAHnya (ia membaca hasil resolusi host).
            'tenant.host' => TenantHost::class,
            'tenant.resolve' => ResolveTenantFromAccount::class,
            // Toggle modul per-pesantren (§5.1, v4.57). Dipakai bersuffix nama modul:
            // ->middleware('modul:keuangan').
            'modul' => PastikanModulAktif::class,
        ]);

        // Tamu di host tenant diantar ke pintu pesantrennya sendiri, bukan ke pintu
        // platform (§1.8 Fase 1). Tanpa ini, wali yang membuka portal tanpa sesi
        // terlempar ke app.walisantri.com/login — lalu login di sana membuat sesi
        // di host yang salah (cookie ber-scope host) dan ia dipantulkan lagi.
        //
        // Host-nya sengaja di-resolve ulang di sini, bukan dibaca dari atribut yang
        // diisi PublicTenantResolver: middleware `auth` punya prioritas lebih tinggi
        // dan berjalan LEBIH DULU dari middleware grup, jadi atribut itu masih kosong
        // saat callback ini dipanggil. Satu query tambahan, dan hanya di jalur tamu.
        $middleware->redirectGuestsTo(function (Request $request) {
            $domain = TenantDomain::where('hostname', $request->getHost())->first();

            return $domain?->pesantren?->url('/login') ?? route('login');
        });

        // SaaSLifecycleLock hanya di panel app (bukan dash/super_admin)
        // Didaftarkan di AdminPanelProvider::middleware(), bukan di web group global,
        // agar tidak menyentuh request dash panel dan public site.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Diagnostik sementara (2026-07-12) — lacak sumber 403 di request Livewire yang
        // dilaporkan user (toast error generik Filament di admin panel production).
        // Sengaja pakai renderable(), BUKAN reportable(): Handler::report() short-circuit
        // di shouldntReport() untuk AuthorizationException/HttpException (dianggap
        // "expected", lihat $internalDontReport) SEBELUM reportable callback sempat
        // jalan — makanya 403 ini tidak pernah masuk log sama sekali selama ini.
        // render() tidak melalui gate itu, jadi callback ini selalu jalan. Selalu
        // return null supaya rendering default Laravel tetap jalan seperti biasa
        // (murni side-effect logging, tidak mengubah response yang dilihat user).
        // HAPUS blok ini setelah root cause ditemukan & diperbaiki.
        $exceptions->renderable(function (Throwable $e, $request) {
            $isForbidden = $e instanceof AuthorizationException
                || ($e instanceof HttpExceptionInterface
                    && $e->getStatusCode() === 403);

            if (! $isForbidden || ! $request->hasHeader('X-Livewire')) {
                return null;
            }

            $components = [];

            try {
                foreach ((array) $request->input('components', []) as $component) {
                    $snapshot = json_decode($component['snapshot'] ?? '', true);
                    $components[] = [
                        'name' => $snapshot['memo']['name'] ?? null,
                        'calls' => array_map(
                            fn ($call) => $call['method'] ?? null,
                            $component['calls'] ?? []
                        ),
                    ];
                }
            } catch (Throwable) {
                $components = ['parse_failed' => true];
            }

            Log::warning('diag_livewire_403', [
                'path' => $request->path(),
                'user_id' => auth()->id(),
                'role' => auth()->user()?->role,
                'pesantren' => auth()->user()?->pesantren_id,
                'components' => $components,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return null;
        });

        // Jaring pengaman: ubah pelanggaran constraint database (QueryException)
        // menjadi pesan berbahasa Indonesia yang bisa dipahami user, alih-alih
        // error 500 / "Server Error" mentah. Ini menutup alur NON-Livewire
        // (controller Wali, registrasi, dsb). Form Filament sudah punya validasi
        // inline sendiri, jadi request Livewire dibiarkan ke mekanismenya.
        $exceptions->renderable(function (QueryException $e, $request) {
            $sqlState = (string) $e->getCode();

            $message = match ($sqlState) {
                '23505' => 'Data yang sama sudah ada. Periksa kembali — kemungkinan data ini sudah pernah diinput.',
                '23503' => 'Data ini masih terkait dengan data lain, atau data acuan yang dipilih tidak ditemukan.',
                '23502' => 'Ada isian wajib yang masih kosong. Lengkapi semua kolom bertanda wajib.',
                '23514' => 'Ada pilihan yang tidak valid pada salah satu isian. Periksa kembali pilihan Anda.',
                default => 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi, atau hubungi admin bila berulang.',
            };

            Log::warning('db_constraint_violation', [
                'sqlstate' => $sqlState,
                'path' => $request->path(),
                'user_id' => auth()->id(),
                'message' => $e->getMessage(),
            ]);

            // Livewire/Filament & permintaan JSON ditangani mekanismenya sendiri.
            if ($request->hasHeader('X-Livewire') || $request->expectsJson()) {
                return null;
            }

            return back()->withInput()->withErrors(['db' => $message]);
        });
    })->create();
