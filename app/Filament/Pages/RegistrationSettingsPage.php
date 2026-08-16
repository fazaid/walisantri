<?php

namespace App\Filament\Pages;

use App\Enums\NavigationGroup;
use App\Enums\UserRole;
use App\Models\BillingSetting;
use App\Models\PlatformSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
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

class RegistrationSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::PengaturanPlatform;

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Pendaftaran';

    protected static ?string $title = 'Pengaturan Pendaftaran';

    protected string $view = 'filament.pages.registration-settings';

    protected static ?string $slug = 'registration-settings-page';

    public bool $registration_open = true;

    public bool $demo_open = true;

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin->value;
    }

    public function mount(): void
    {
        $this->form->fill([
            'registration_open' => PlatformSetting::registrationOpen(),
            'demo_open' => PlatformSetting::demoOpen(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Pendaftaran Mandiri')
                ->description('Mengatur akses ke halaman /register, tempat pesantren baru mendaftar sendiri dan langsung mendapat trial '.BillingSetting::get('trial_days', 14).' hari.')
                ->schema([
                    Toggle::make('registration_open')
                        ->label('Buka halaman pendaftaran mandiri (/register)')
                        ->helperText('Matikan sebagai kill-switch cepat, misalnya saat onboarding sengaja dialihkan lewat Antrean Demo, tanpa perlu ubah .env atau deploy ulang.')
                        ->default(true),
                ]),

            Section::make('Ajukan Demo')
                ->description('Mengatur akses ke halaman /demo, tempat calon pesantren meninggalkan datanya untuk masuk ke Antrean Demo.')
                ->schema([
                    Toggle::make('demo_open')
                        ->label('Buka halaman ajukan demo (/demo)')
                        ->helperText('Kalau keduanya dimatikan, landing page tidak lagi menampilkan tombol ajakan apa pun — hanya pemberitahuan bahwa pendaftaran sedang ditutup.')
                        ->default(true),
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
        $state = $this->form->getState();

        PlatformSetting::set('registration_open', (bool) $state['registration_open']);
        PlatformSetting::set('demo_open', (bool) $state['demo_open']);

        Notification::make()
            ->title('Pengaturan registrasi berhasil disimpan')
            ->success()
            ->send();
    }
}
