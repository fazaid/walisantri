<?php

namespace App\Providers\Filament;

use App\Filament\Resources\Pesantrens\PesantrenResource;
use App\Filament\Widgets\ExpiringTenantsWidget;
use App\Filament\Widgets\PengumumanCentralWidget;
use App\Filament\Widgets\SystemStatsWidget;
use App\Filament\Widgets\TenantListWidget;
use App\Filament\Widgets\TenantStatsOverview;
use App\Http\Middleware\SaaSLifecycleLock;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class DashPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('dash')
            ->path('admin')
            ->domain(config('app.dash_domain', 'dash.walisantri.com'))
            ->login()
            ->colors([
                'primary' => Color::Rose,
            ])
            ->resources([
                PesantrenResource::class,
            ])
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                AccountWidget::class,
                TenantStatsOverview::class,
                SystemStatsWidget::class,
                ExpiringTenantsWidget::class,
                TenantListWidget::class,
                PengumumanCentralWidget::class,
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
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
