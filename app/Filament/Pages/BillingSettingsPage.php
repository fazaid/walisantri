<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\BillingSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class BillingSettingsPage extends Page
{
    protected string $view = 'filament.pages.billing-settings';

    protected static string|UnitEnum|null $navigationGroup = 'Langganan';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Pengaturan Harga';

    protected static ?string $title = 'Pengaturan Harga Langganan';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    public array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin->value;
    }

    public function mount(): void
    {
        $this->data = BillingSetting::allAsArray();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Harga Paket (per bulan)')
                    ->description('Harga dalam Rupiah, tanpa titik/koma.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('harga_rintisan')
                            ->label('Rintisan (≤100 santri)')
                            ->numeric()->minValue(0)->required()
                            ->prefix('Rp'),
                        TextInput::make('harga_berkembang')
                            ->label('Berkembang (≤500 santri)')
                            ->numeric()->minValue(0)->required()
                            ->prefix('Rp'),
                        TextInput::make('harga_maju_base')
                            ->label('Maju — harga dasar (≤1.000 santri)')
                            ->numeric()->minValue(0)->required()
                            ->prefix('Rp'),
                        TextInput::make('harga_maju_per_100_santri')
                            ->label('Maju — tambahan per 100 santri di atas 1.000')
                            ->numeric()->minValue(0)->required()
                            ->prefix('Rp'),
                    ]),

                Section::make('Kuota Santri per Paket')
                    ->columns(3)
                    ->schema([
                        TextInput::make('kuota_rintisan')
                            ->label('Rintisan')->numeric()->minValue(1)->required()
                            ->suffix('santri'),
                        TextInput::make('kuota_berkembang')
                            ->label('Berkembang')->numeric()->minValue(1)->required()
                            ->suffix('santri'),
                        TextInput::make('kuota_maju_base')
                            ->label('Maju (dasar)')->numeric()->minValue(1)->required()
                            ->suffix('santri'),
                    ]),

                Section::make('Diskon Berlangganan Tahunan')
                    ->columns(1)
                    ->schema([
                        TextInput::make('bonus_bulan_tahunan')
                            ->label('Bonus bulan gratis saat berlangganan 12 bulan')
                            ->numeric()->minValue(0)->maxValue(6)->required()
                            ->suffix('bulan')
                            ->helperText('Contoh: nilai 2 → bayar 10 bulan, aktif 12 bulan'),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Pengaturan')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        BillingSetting::saveMany($this->form->getState());

        Notification::make()
            ->title('Pengaturan harga berhasil disimpan')
            ->success()
            ->send();
    }
}
