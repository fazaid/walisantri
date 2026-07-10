<?php

// File: app/Http/Middleware/SaaSLifecycleLock.php
// php artisan make:middleware SaaSLifecycleLock

namespace App\Http\Middleware;

use App\Filament\Pages\BillingPage;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SaaSLifecycleLock
{
    // Role yang boleh bypass lock (super_admin selalu lolos)
    private const BYPASS_ROLES = ['super_admin'];

    // Wali santri mendapat grace period 7 hari setelah expired
    private const WALI_GRACE_DAYS = 7;

    public function handle(Request $request, Closure $next): Response
    {
        // Belum login — biarkan lewat, AuthMiddleware yang handle
        if (! auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        // Selalu izinkan request logout agar user bisa keluar meski status terkunci
        if ($request->is('admin/logout')
            || $request->is('wali/logout')
            || $request->routeIs('filament.admin.auth.logout')
            || $request->routeIs('logout')
        ) {
            return $next($request);
        }

        // Super admin tidak pernah dikunci
        if (in_array($user->role, self::BYPASS_ROLES)) {
            return $next($request);
        }

        $pesantren = $user->pesantren;

        if (! $pesantren) {
            return $this->lockResponse($request, 'Data pesantren tidak ditemukan.');
        }

        // Halaman billing, invoice, upgrade, & auth panel selalu boleh diakses
        // (mencegah infinite redirect). Dicek via route name, bukan path string —
        // BillingPage ada di dalam Cluster PengaturanPesantren (slug "pengaturan"),
        // jadi path-nya bukan lagi "admin/billing-page" melainkan
        // "admin/pengaturan/billing-page". Route name tetap stabil walau halaman
        // dipindah cluster di masa depan.
        if ($request->routeIs('filament.admin.pengaturan.pages.billing-page')
            || $request->routeIs('filament.admin.pages.order-invoice-page')
            || $request->routeIs('filament.admin.pages.upgrade-page')
            || $request->is('admin/login')
            || $request->is('admin/logout')
            || $request->routeIs('filament.admin.auth.*')
        ) {
            return $next($request);
        }

        // Auto-sinkronkan status ke 'expired' jika expired_at sudah lewat
        if ($this->isExpired($pesantren)
            && ! in_array($pesantren->status_berlangganan, ['expired', 'suspended'])
        ) {
            $pesantren->updateQuietly(['status_berlangganan' => 'expired']);
            $pesantren->status_berlangganan = 'expired';
        }

        // Status suspended — semua role dikunci total
        if ($pesantren->status_berlangganan === 'suspended') {
            if ($user->isWaliSantri()) {
                return $this->lockResponse($request,
                    'Akses portal wali santri telah ditutup. Hubungi pihak pesantren.',
                    423
                );
            }
            return $this->redirectBilling($request, $pesantren);
        }

        // Status expired
        if ($pesantren->status_berlangganan === 'expired' || $this->isExpired($pesantren)) {

            // Wali santri: grace period 7 hari, read-only
            if ($user->isWaliSantri()) {
                // Absolute diperlukan eksplisit — Carbon 3 mengubah default diffInDays()
                // jadi signed (beda dari Carbon 2), expired_at selalu di masa lalu di sini
                // jadi tanpa $absolute=true hasilnya negatif dan grace period tidak pernah habis.
                $daysSinceExpired = now()->diffInDays($pesantren->expired_at, true);

                if ($daysSinceExpired > self::WALI_GRACE_DAYS) {
                    return $this->lockResponse($request,
                        'Masa tenggang akses wali santri telah berakhir.',
                        423
                    );
                }

                // Dalam grace period: abort semua mutasi
                if (! $request->isMethod('GET')) {
                    abort(403, 'Akses read-only selama masa tenggang.');
                }

                // Sisipkan flag banner peringatan ke request
                $request->attributes->set('grace_period_warning', true);
                $request->attributes->set('grace_days_left',
                    self::WALI_GRACE_DAYS - $daysSinceExpired
                );

                return $next($request);
            }

            // Admin & Ustadz: redirect ke billing
            return $this->redirectBilling($request, $pesantren);
        }

        return $next($request);
    }

    private function isExpired($pesantren): bool
    {
        return $pesantren->expired_at && now()->isAfter($pesantren->expired_at);
    }

    private function redirectBilling(Request $request, $pesantren): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Langganan pesantren telah berakhir. Silakan perbarui pembayaran.',
            ], 402);
        }

        return redirect(BillingPage::getUrl());
    }

    private function lockResponse(Request $request, string $message, int $status = 403): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        abort($status, $message);
    }
}