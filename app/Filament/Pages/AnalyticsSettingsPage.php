<?php

namespace App\Filament\Pages;

use App\Enums\NavigationGroup;
use App\Enums\UserRole;
use App\Models\AnalyticsSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use UnitEnum;

class AnalyticsSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::PengaturanPlatform;

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Analytics';

    protected static ?string $title = 'Pengaturan Analytics';

    protected string $view = 'filament.pages.analytics-settings';

    protected static ?string $slug = 'analytics-settings-page';

    public bool $enabled = true;

    public ?string $gtm_id = null;

    public ?string $ga4_id = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin->value;
    }

    public function mount(): void
    {
        $this->form->fill([
            'enabled' => AnalyticsSetting::enabled(),
            'gtm_id' => AnalyticsSetting::gtmId(),
            'ga4_id' => AnalyticsSetting::ga4Id(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Kode Tracking')
                ->description('Pasang Google Tag Manager dan/atau Google Analytics 4 di seluruh situs (walisantri.com, app.walisantri.com, dan profil publik pesantren). Isi salah satu atau keduanya. Kosongkan untuk menonaktifkan.')
                ->schema([
                    Toggle::make('enabled')
                        ->label('Aktifkan tracking')
                        ->helperText('Kill-switch cepat. Matikan untuk menghentikan semua tracking tanpa menghapus ID yang sudah tersimpan.')
                        ->default(true),

                    TextInput::make('gtm_id')
                        ->label('GTM Container ID')
                        ->placeholder('GTM-XXXXXXX')
                        ->maxLength(50)
                        ->rule('regex:/^(GTM-[A-Za-z0-9]+)?$/')
                        ->validationMessages([
                            'regex' => 'Format GTM Container ID tidak valid. Contoh yang benar: GTM-ABC1234.',
                        ])
                        ->helperText('Dari tagmanager.google.com → Container tipe Web. Kosongkan bila tidak memakai GTM.'),

                    TextInput::make('ga4_id')
                        ->label('GA4 Measurement ID')
                        ->placeholder('G-XXXXXXXXXX')
                        ->maxLength(50)
                        ->rule('regex:/^(G-[A-Za-z0-9]+)?$/')
                        ->validationMessages([
                            'regex' => 'Format GA4 Measurement ID tidak valid. Contoh yang benar: G-ABCD1234EF.',
                        ])
                        ->helperText('Dari analytics.google.com → Admin → Data Streams. Kosongkan bila GA4 diatur dari dalam GTM.'),

                    Placeholder::make('panduan')
                        ->label('Langkah singkat')
                        ->content(new HtmlString(
                            '<ol style="list-style:decimal;margin-left:1rem;line-height:1.7">'
                            .'<li>Buka <b>tagmanager.google.com</b> → buat Container tipe <b>Web</b> → salin <b>GTM-XXXX</b>.</li>'
                            .'<li>Buka <b>analytics.google.com</b> → buat properti GA4 → salin <b>G-XXXX</b>.</li>'
                            .'<li>Di GTM: <b>Tags → New → Google Analytics: GA4 Configuration</b> → tempel <b>G-XXXX</b> → trigger <b>All Pages</b> → <b>Submit/Publish</b>.</li>'
                            .'<li>Masukkan <b>GTM-XXXX</b> di kolom di atas lalu Simpan. (Cara cepat tanpa GTM: cukup isi kolom GA4 <b>G-XXXX</b>.)</li>'
                            .'</ol>'
                        )),
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

        try {
            AnalyticsSetting::set('gtm_id', filled($state['gtm_id']) ? trim($state['gtm_id']) : null);
            AnalyticsSetting::set('ga4_id', filled($state['ga4_id']) ? trim($state['ga4_id']) : null);
            AnalyticsSetting::set('enabled', $state['enabled'] ? '1' : '0');
        } catch (\Throwable $e) {
            Log::error('analytics_settings_save_failed', ['message' => $e->getMessage()]);

            Notification::make()
                ->title('Gagal menyimpan pengaturan')
                ->body('Terjadi kesalahan saat menyimpan. Silakan coba lagi, atau hubungi admin bila berulang.')
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Pengaturan analytics berhasil disimpan')
            ->success()
            ->send();
    }
}
