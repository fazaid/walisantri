<?php

namespace App\Providers\Filament;

use App\Filament\Pages\EditProfile;
use App\Http\Middleware\CheckTenantQuota;
use App\Http\Middleware\FilamentAuthenticate;
use App\Http\Middleware\ResolveTenantFromAccount;
use App\Http\Middleware\SaaSLifecycleLock;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
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
            ->favicon(asset('favicon.svg'))
            ->colors([
                'primary' => Color::Teal,
            ])
            ->profile(EditProfile::class, isSimple: false)
            ->databaseNotifications()
            ->sidebarFullyCollapsibleOnDesktop()
            ->navigationGroups([
                'Kesantrian',
                'Langganan',
                'Manajemen',
            ])
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
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => view('filament.admin.bottom-nav')->render(),
            )
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
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
