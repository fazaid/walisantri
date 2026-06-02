<?php

namespace App\Filament\Pages;

use App\Models\Order;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class BillingPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen';

    protected static ?string $navigationLabel = 'Billing';

    protected static ?string $title = 'Informasi Langganan';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.billing-page';

    public static function canAccess(): bool
    {
        return Auth::user()?->role === 'admin_pesantren';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upgrade')
                ->label('Upgrade / Perpanjang Paket')
                ->icon(Heroicon::OutlinedArrowUpCircle)
                ->url(UpgradePage::getUrl())
                ->color('primary'),
        ];
    }

    public function getPesantren()
    {
        return Auth::user()?->pesantren;
    }

    public function getActiveOrder(): ?Order
    {
        return $this->getPesantren()?->activeOrder;
    }
}
