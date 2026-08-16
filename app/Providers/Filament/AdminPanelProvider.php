<?php

namespace App\Providers\Filament;

use App\Enums\NavigationGroup;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\EditProfile;
use App\Http\Middleware\CheckTenantQuota;
use App\Http\Middleware\FilamentAuthenticate;
use App\Http\Middleware\ResolveTenantFromAccount;
use App\Http\Middleware\SaaSLifecycleLock;
use App\Models\PlatformBrandingSetting;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Livewire\Livewire;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->domain(config('app.domain', 'app.walisantri.com'))
            ->favicon(fn () => PlatformBrandingSetting::faviconUrl())
            ->brandLogo(fn () => PlatformBrandingSetting::logoUrl())
            ->brandLogoHeight('2rem')
            ->colors([
                'primary' => Color::Teal,
            ])
            ->profile(EditProfile::class, isSimple: false)
            ->databaseNotifications()
            ->databaseNotificationsPolling('5m')
            // Matikan handler error notification bawaan Filament. onFailure-nya (kegagalan
            // jaringan, mis. wake-from-sleep sebelum WiFi pulih) SELALU memunculkan toast
            // generik tanpa cek status & tanpa bisa dicegah interceptor lain. Kita gantikan
            // dengan handler sendiri di resources/views/filament/admin/session-expired-handler.blade.php
            // yang diam untuk kegagalan jaringan tapi tetap memberi tahu error server asli.
            ->errorNotifications(false)
            ->sidebarFullyCollapsibleOnDesktop()
            // Nama & urutan grup berasal dari App\Enums\NavigationGroup.
            // Filament menerima class-string enum dan meng-enumerasi cases() sendiri.
            ->navigationGroups(NavigationGroup::class)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\Filament\Clusters')
            ->pages([
                Dashboard::class,
            ])
            ->renderHook(
                PanelsRenderHook::PAGE_START,
                function (): string {
                    $livewire = Livewire::current();

                    if (! $livewire || ! method_exists($livewire, 'getCachedSubNavigation')) {
                        return '';
                    }

                    if ($livewire::getSubNavigationPosition() !== SubNavigationPosition::Top) {
                        return '';
                    }

                    $navigation = $livewire->getCachedSubNavigation();

                    if (blank($navigation)) {
                        return '';
                    }

                    return view('filament-panels::components.page.sub-navigation.tabs', [
                        'navigation' => $navigation,
                    ])->render();
                },
            )
            // Spanduk konfirmasi email — merender kosong kecuali untuk admin
            // pesantren yang alamatnya belum dikonfirmasi (§12.2). Tidak memblokir
            // apa pun; hook yang sama sudah dipakai di atas untuk tab sub-navigasi.
            ->renderHook(
                PanelsRenderHook::PAGE_START,
                fn (): string => view('filament.admin.verifikasi-email-banner')->render(),
            )
            // Pencarian global dimatikan dan ditukar tautan profil pesantren
            // (keputusan pemilik produk). Pencarian itu tidak pernah dikurasi:
            // lima resource ikut terindeks lewat id/tanggal/jam, sehingga mengetik
            // angka memunculkan hasil sampah. Lihat tautan-profil.blade.php.
            ->globalSearch(false)
            // GLOBAL_SEARCH_AFTER, bukan TOPBAR_END: hook itu dirender di LUAR
            // .fi-topbar-end sehingga tautannya selalu terlempar paling kanan,
            // melewati lonceng notifikasi dan menu pengguna. Hook ini tetap
            // dirender meski pencarian globalnya dimatikan.
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn (): string => view('filament.admin.tautan-profil')->render(),
            )
            // Urutan topbar ditentukan urutan pendaftaran hook: profil → bantuan →
            // lonceng notifikasi → menu pengguna.
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn (): string => view('filament.admin.tombol-bantuan')->render(),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => view('filament.admin.bottom-nav')->render(),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => view('filament.admin.session-expired-handler')->render(),
            )
            // Fallback clipboard untuk kolom/entry copyable() di luar secure context.
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => view('filament.admin.clipboard-fallback')->render(),
            )
            // Google Tag Manager / GA4 — dikelola dari Pengaturan Analytics (super admin).
            // Partial merender kosong bila tracking nonaktif / ID belum diisi.
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => view('partials.analytics-head')->render(),
            )
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn (): string => view('partials.analytics-body')->render(),
            )
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SaaSLifecycleLock::class,
                CheckTenantQuota::class,
            ])
            ->authMiddleware([
                FilamentAuthenticate::class,
                ResolveTenantFromAccount::class,
            ]);
    }
}
