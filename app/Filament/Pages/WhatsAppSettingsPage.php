<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\WhatsAppGatewaySetting;
use App\Models\WhatsAppMessageTemplate;
use App\Models\WhatsAppSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
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
use UnitEnum;

class WhatsAppSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Langganan';

    protected static ?int $navigationSort = 11;

    protected static ?string $navigationLabel = 'Pengaturan WhatsApp';

    protected static ?string $title = 'Pengaturan WhatsApp';

    protected string $view = 'filament.pages.whatsapp-settings';

    protected static ?string $slug = 'whatsapp-settings-page';

    public bool $reminder_expired_enabled = true;

    public string $reminder_expired_template = '';

    public bool $notif_trial_habis_enabled = true;

    public string $notif_trial_habis_template = '';

    public bool $notif_order_dikonfirmasi_enabled = true;

    public string $notif_order_dikonfirmasi_template = '';

    public bool $notif_demo_terima_kasih_enabled = true;

    public string $notif_demo_terima_kasih_template = '';

    public ?string $fonnte_token = null;

    public ?string $fonnte_token_last4 = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin->value;
    }

    public function mount(): void
    {
        $this->refreshFonnteTokenIndicator();

        $this->form->fill([
            'reminder_expired_enabled' => WhatsAppSetting::get('reminder_expired_enabled'),
            'reminder_expired_template' => WhatsAppMessageTemplate::get('reminder_expired'),
            'notif_trial_habis_enabled' => WhatsAppSetting::get('notif_trial_habis_enabled'),
            'notif_trial_habis_template' => WhatsAppMessageTemplate::get('notif_trial_habis'),
            'notif_order_dikonfirmasi_enabled' => WhatsAppSetting::get('notif_order_dikonfirmasi_enabled'),
            'notif_order_dikonfirmasi_template' => WhatsAppMessageTemplate::get('notif_order_dikonfirmasi'),
            'notif_demo_terima_kasih_enabled' => WhatsAppSetting::get('notif_demo_terima_kasih_enabled'),
            'notif_demo_terima_kasih_template' => WhatsAppMessageTemplate::get('notif_demo_terima_kasih'),
        ]);
    }

    // Token TIDAK PERNAH di-prefill ke field form — Livewire menyerialisasi public
    // property ke wire:snapshot di HTML, jadi nilai asli akan bocor ke DOM meski
    // tampil masked secara visual. Hanya 4 karakter terakhir yang aman ditampilkan.
    private function refreshFonnteTokenIndicator(): void
    {
        $this->fonnte_token = null;

        $token = WhatsAppGatewaySetting::get('fonnte_token');
        $this->fonnte_token_last4 = $token ? substr($token, -4) : null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Reminder Billing')
                ->description('Pengecualian sempit atas kebijakan WhatsApp manual (PRD §12) — hanya mengatur reminder billing H-3/H-1, tidak memengaruhi fitur WA lain (Magic Link, broadcast wali, rapor, dsb).')
                ->schema([
                    Toggle::make('reminder_expired_enabled')
                        ->label('Kirim reminder WhatsApp H-3/H-1 sebelum langganan expired')
                        ->helperText('Matikan sebagai kill-switch cepat, misalnya saat gateway Fonnte bermasalah atau kuota habis, tanpa perlu deploy ulang.')
                        ->default(true),
                    Textarea::make('reminder_expired_template')
                        ->label('Template pesan reminder')
                        ->required()
                        ->rows(8)
                        ->helperText('Placeholder yang bisa dipakai: {nama_pesantren}, {sisa_hari}, {tanggal_expired}, {link_billing}.'),
                ]),
            Section::make('Notifikasi Trial/Langganan Habis')
                ->description('Pengecualian sempit kedua atas kebijakan WhatsApp manual (PRD §12) — notifikasi sekali saat status baru saja berubah ke expired, tidak memengaruhi fitur WA lain.')
                ->schema([
                    Toggle::make('notif_trial_habis_enabled')
                        ->label('Kirim notifikasi WhatsApp saat langganan baru saja expired')
                        ->helperText('Matikan sebagai kill-switch cepat, misalnya saat gateway Fonnte bermasalah atau kuota habis, tanpa perlu deploy ulang.')
                        ->default(true),
                    Textarea::make('notif_trial_habis_template')
                        ->label('Template pesan notifikasi expired')
                        ->required()
                        ->rows(8)
                        ->helperText('Placeholder yang bisa dipakai: {nama_pesantren}, {tanggal_expired}, {link_billing}.'),
                ]),
            Section::make('Notifikasi Order Dikonfirmasi')
                ->description('Pengecualian sempit ketiga atas kebijakan WhatsApp manual (PRD §12) — notifikasi otomatis saat Super Admin mengonfirmasi order upgrade/perpanjangan, tidak memengaruhi fitur WA lain.')
                ->schema([
                    Toggle::make('notif_order_dikonfirmasi_enabled')
                        ->label('Kirim notifikasi WhatsApp saat order dikonfirmasi Super Admin')
                        ->helperText('Matikan sebagai kill-switch cepat, misalnya saat gateway Fonnte bermasalah atau kuota habis, tanpa perlu deploy ulang.')
                        ->default(true),
                    Textarea::make('notif_order_dikonfirmasi_template')
                        ->label('Template pesan order dikonfirmasi')
                        ->required()
                        ->rows(8)
                        ->helperText('Placeholder yang bisa dipakai: {nama_pesantren}, {paket}, {durasi_bulan}, {tanggal_expired}, {nomor_order}, {total_dibayar}, {link_billing}.'),
                ]),
            Section::make('Notifikasi Terima Kasih Demo')
                ->description('Pengecualian sempit keempat atas kebijakan WhatsApp manual (PRD §12) — ucapan terima kasih + link grup support otomatis saat calon pelanggan mengisi form demo, tidak memengaruhi fitur WA lain.')
                ->schema([
                    Toggle::make('notif_demo_terima_kasih_enabled')
                        ->label('Kirim ucapan terima kasih WhatsApp saat form demo disubmit')
                        ->helperText('Matikan sebagai kill-switch cepat, misalnya saat gateway Fonnte bermasalah atau kuota habis, tanpa perlu deploy ulang.')
                        ->default(true),
                    Textarea::make('notif_demo_terima_kasih_template')
                        ->label('Template pesan terima kasih demo')
                        ->required()
                        ->rows(8)
                        ->helperText('Placeholder yang bisa dipakai: {nama_kontak}, {nama_pesantren}. Link grup WhatsApp support diketik langsung di dalam template ini.'),
                ]),
            Section::make('Koneksi Gateway Fonnte')
                ->description('Token API akun Fonnte yang dipakai untuk mengirim SEMUA notifikasi WhatsApp platform. Mengganti token di sini langsung berlaku tanpa redeploy/edit .env server.')
                ->schema([
                    Placeholder::make('fonnte_token_status')
                        ->label('Token saat ini')
                        ->content(fn () => $this->fonnte_token_last4
                            ? "Tersimpan di database, berakhiran ...{$this->fonnte_token_last4}"
                            : 'Belum diatur di database — memakai FONNTE_TOKEN dari .env server.'),
                    TextInput::make('fonnte_token')
                        ->label('Token Fonnte baru')
                        ->password()
                        ->revealable()
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->helperText('Kosongkan jika tidak ingin mengubah token yang sudah tersimpan. Diisi hanya saat ingin mengganti/rotasi token.'),
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

        WhatsAppSetting::set('reminder_expired_enabled', (bool) $state['reminder_expired_enabled']);
        WhatsAppMessageTemplate::set('reminder_expired', $state['reminder_expired_template']);

        WhatsAppSetting::set('notif_trial_habis_enabled', (bool) $state['notif_trial_habis_enabled']);
        WhatsAppMessageTemplate::set('notif_trial_habis', $state['notif_trial_habis_template']);

        WhatsAppSetting::set('notif_order_dikonfirmasi_enabled', (bool) $state['notif_order_dikonfirmasi_enabled']);
        WhatsAppMessageTemplate::set('notif_order_dikonfirmasi', $state['notif_order_dikonfirmasi_template']);

        WhatsAppSetting::set('notif_demo_terima_kasih_enabled', (bool) $state['notif_demo_terima_kasih_enabled']);
        WhatsAppMessageTemplate::set('notif_demo_terima_kasih', $state['notif_demo_terima_kasih_template']);

        if (isset($state['fonnte_token'])) {
            WhatsAppGatewaySetting::set('fonnte_token', $state['fonnte_token']);
        }

        $this->refreshFonnteTokenIndicator();

        Notification::make()
            ->title('Pengaturan WhatsApp berhasil disimpan')
            ->success()
            ->send();
    }
}
