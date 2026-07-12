<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Daftarkan alias untuk dipanggil via #[Middleware] atau route middleware
        $middleware->alias([
            'tenant.quota'   => \App\Http\Middleware\CheckTenantQuota::class,
            'saas.lifecycle' => \App\Http\Middleware\SaaSLifecycleLock::class,
            'magic.token'    => \App\Http\Middleware\VerifyMagicToken::class,
            'public.tenant'  => \App\Http\Middleware\PublicTenantResolver::class,
            'tenant.resolve' => \App\Http\Middleware\ResolveTenantFromAccount::class,
        ]);

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
        $exceptions->renderable(function (\Throwable $e, $request) {
            $isForbidden = $e instanceof \Illuminate\Auth\Access\AuthorizationException
                || ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                    && $e->getStatusCode() === 403);

            if (! $isForbidden || ! $request->hasHeader('X-Livewire')) {
                return null;
            }

            $components = [];

            try {
                foreach ((array) $request->input('components', []) as $component) {
                    $snapshot = json_decode($component['snapshot'] ?? '', true);
                    $components[] = [
                        'name'  => $snapshot['memo']['name'] ?? null,
                        'calls' => array_map(
                            fn ($call) => $call['method'] ?? null,
                            $component['calls'] ?? []
                        ),
                    ];
                }
            } catch (\Throwable) {
                $components = ['parse_failed' => true];
            }

            \Illuminate\Support\Facades\Log::warning('diag_livewire_403', [
                'path'       => $request->path(),
                'user_id'    => auth()->id(),
                'role'       => auth()->user()?->role,
                'pesantren'  => auth()->user()?->pesantren_id,
                'components' => $components,
                'exception'  => get_class($e),
                'message'    => $e->getMessage(),
            ]);

            return null;
        });
    })->create();
