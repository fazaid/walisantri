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
        ]);

        // SaaSLifecycleLock jalan di semua request authenticated (kecuali super_admin)
        // Tambahkan ke grup 'web' agar otomatis aktif
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\SaaSLifecycleLock::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
