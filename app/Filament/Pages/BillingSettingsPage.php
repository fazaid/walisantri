<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\BillingSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class BillingSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Langganan';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Pengaturan Harga';

    protected static ?string $title = 'Pengaturan Harga Langganan';

    protected string $view = 'filament.pages.billing-settings';

    public int $harga_rintisan           = 0;
    public int $harga_berkembang          = 0;
    public int $harga_maju_base           = 0;
    public int $harga_maju_per_100_santri = 0;
    public int $kuota_rintisan            = 0;
    public int $kuota_berkembang          = 0;
    public int $kuota_maju_base           = 0;
    public int $bonus_bulan_enam          = 0;
    public int $bonus_bulan_tahunan       = 0;

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin->value;
    }

    public function mount(): void
    {
        $this->form->fill(BillingSetting::allAsArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Harga Paket (per bulan)')
                ->description('Dalam Rupiah penuh, tanpa titik atau koma.')
                ->columns(2)
                ->schema([
                    TextInput::make('harga_rintisan')
                        ->label('Rintisan')
                        ->numeric()->minValue(0)->required()
                        ->prefix('Rp'),
                    TextInput::make('harga_berkembang')
                        ->label('Berkembang')
                        ->numeric()->minValue(0)->required()
                        ->prefix('Rp'),
                    TextInput::make('harga_maju_base')
                        ->label('Maju — harga dasar')
                        ->numeric()->minValue(0)->required()
                        ->prefix('Rp')
                        ->helperText('Berlaku untuk paket Maju sampai batas kuota dasar'),
                    TextInput::make('harga_maju_per_100_santri')
                        ->label('Maju — tambahan per 100 santri')
                        ->numeric()->minValue(0)->required()
                        ->prefix('Rp')
                        ->helperText('Dikenakan setiap kelipatan 100 santri di atas kuota dasar'),
                ]),

            Section::make('Kuota Santri per Paket')
                ->columns(4)
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

            Section::make('Diskon Durasi Berlangganan')
                ->columns(2)
                ->schema([
                    TextInput::make('bonus_bulan_enam')
                        ->label('Bonus bulan gratis — langganan 6 bulan')
                        ->numeric()->minValue(0)->maxValue(3)->required()
                        ->suffix('bulan')
                        ->helperText('Contoh: nilai 1 → bayar 5 bulan, aktif 6 bulan'),
                    TextInput::make('bonus_bulan_tahunan')
                        ->label('Bonus bulan gratis — langganan 12 bulan')
                        ->numeric()->minValue(0)->maxValue(6)->required()
                        ->suffix('bulan')
                        ->helperText('Contoh: nilai 2 → bayar 10 bulan, aktif 12 bulan'),
                ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Simpan Pengaturan')
                            ->submit('save'),
                    ])->key('form-actions'),
                ]),
        ]);
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
