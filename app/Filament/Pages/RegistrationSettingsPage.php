<?php

namespace App\Filament\Pages;

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

    protected static string|UnitEnum|null $navigationGroup = 'Langganan';

    protected static ?int $navigationSort = 12;

    protected static ?string $navigationLabel = 'Pengaturan Registrasi';

    protected static ?string $title = 'Pengaturan Registrasi';

    protected string $view = 'filament.pages.registration-settings';

    protected static ?string $slug = 'registration-settings-page';

    public bool $registration_open = true;

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin->value;
    }

    public function mount(): void
    {
        $this->form->fill([
            'registration_open' => PlatformSetting::get('registration_open', config('app.registration_open', true)),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Pendaftaran Mandiri')
                ->description('Mengatur akses ke halaman /register, tempat pesantren baru mendaftar sendiri dan langsung mendapat trial ' . BillingSetting::get('trial_days', 14) . ' hari.')
                ->schema([
                    Toggle::make('registration_open')
                        ->label('Buka halaman pendaftaran mandiri (/register)')
                        ->helperText('Matikan sebagai kill-switch cepat, misalnya saat onboarding sengaja dialihkan lewat Antrean Demo, tanpa perlu ubah .env atau deploy ulang.')
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

        Notification::make()
            ->title('Pengaturan registrasi berhasil disimpan')
            ->success()
            ->send();
    }
}
