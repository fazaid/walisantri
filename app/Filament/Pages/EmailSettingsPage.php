<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Mail\EmailUji;
use App\Models\EmailGatewaySetting;
use App\Models\EmailSetting;
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
use Illuminate\Support\Facades\Mail;
use UnitEnum;

class EmailSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'Langganan';

    protected static ?int $navigationSort = 12;

    protected static ?string $navigationLabel = 'Pengaturan Email';

    protected static ?string $title = 'Pengaturan Email';

    protected string $view = 'filament.pages.email-settings';

    protected static ?string $slug = 'email-settings-page';

    public bool $email_sambutan_enabled = true;

    public bool $email_reset_password_enabled = true;

    public bool $email_invoice_enabled = true;

    public bool $email_pembayaran_enabled = true;

    public bool $email_reminder_expired_enabled = true;

    public ?string $smtp_host = null;

    public ?string $smtp_port = null;

    public ?string $smtp_scheme = null;

    public ?string $smtp_username = null;

    public ?string $smtp_password = null;

    public ?string $smtp_password_last4 = null;

    public ?string $from_address = null;

    public ?string $from_name = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin->value;
    }

    public function mount(): void
    {
        $this->refreshPasswordIndicator();

        $this->form->fill([
            'email_sambutan_enabled' => EmailSetting::get('email_sambutan_enabled'),
            'email_reset_password_enabled' => EmailSetting::get('email_reset_password_enabled'),
            'email_invoice_enabled' => EmailSetting::get('email_invoice_enabled'),
            'email_pembayaran_enabled' => EmailSetting::get('email_pembayaran_enabled'),
            'email_reminder_expired_enabled' => EmailSetting::get('email_reminder_expired_enabled'),
            'smtp_host' => EmailGatewaySetting::get('smtp_host'),
            'smtp_port' => EmailGatewaySetting::get('smtp_port'),
            'smtp_scheme' => EmailGatewaySetting::get('smtp_scheme'),
            'smtp_username' => EmailGatewaySetting::get('smtp_username'),
            'from_address' => EmailGatewaySetting::get('from_address'),
            'from_name' => EmailGatewaySetting::get('from_name'),
        ]);
    }

    // Kata sandi SMTP TIDAK PERNAH di-prefill ke field form — Livewire
    // menyerialisasi public property ke wire:snapshot di HTML, jadi nilai asli
    // bocor ke DOM meski tampil masked secara visual. Hanya 4 karakter terakhir
    // yang aman ditampilkan sebagai penanda "sudah terisi yang mana".
    private function refreshPasswordIndicator(): void
    {
        $this->smtp_password = null;

        $sandi = EmailGatewaySetting::get('smtp_password');
        $this->smtp_password_last4 = $sandi ? substr($sandi, -4) : null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Koneksi SMTP (Brevo)')
                ->description('Kredensial pengirim untuk SEMUA email platform (PRD §12.2). Disimpan terenkripsi di database, sehingga bisa diganti tanpa menyentuh .env di server. Selama host masih kosong, aplikasi memakai konfigurasi .env — di production itu berarti email hanya ditulis ke log, tidak terkirim.')
                ->schema([
                    TextInput::make('smtp_host')
                        ->label('Host SMTP')
                        ->placeholder('smtp-relay.brevo.com')
                        ->maxLength(191),
                    TextInput::make('smtp_port')
                        ->label('Port')
                        ->numeric()
                        ->placeholder('587'),
                    TextInput::make('smtp_scheme')
                        ->label('Enkripsi')
                        ->placeholder('tls')
                        ->maxLength(20)
                        ->helperText('Biasanya "tls" untuk port 587. Kosongkan bila mengikuti bawaan.'),
                    TextInput::make('smtp_username')
                        ->label('Login SMTP')
                        ->maxLength(191)
                        ->helperText('Login SMTP dari dashboard Brevo — bukan email akun Anda.'),
                    Placeholder::make('smtp_password_status')
                        ->label('Kunci SMTP saat ini')
                        ->content(fn () => $this->smtp_password_last4
                            ? "Tersimpan di database, berakhiran ...{$this->smtp_password_last4}"
                            : 'Belum diatur — memakai MAIL_PASSWORD dari .env server.'),
                    TextInput::make('smtp_password')
                        ->label('Kunci SMTP baru')
                        ->password()
                        ->revealable()
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->helperText('Kosongkan jika tidak ingin mengubah kunci yang sudah tersimpan. Diisi hanya saat mengganti/rotasi kunci.'),
                ]),
            Section::make('Identitas Pengirim')
                ->description('Alamat ini harus berada di domain yang sudah terverifikasi di Brevo (SPF & DKIM terpasang). Memakai alamat domain lain — termasuk Gmail — membuat email masuk folder spam atau ditolak penerima.')
                ->schema([
                    TextInput::make('from_address')
                        ->label('Alamat pengirim')
                        ->email()
                        ->placeholder('noreply@walisantri.com')
                        ->maxLength(191),
                    TextInput::make('from_name')
                        ->label('Nama pengirim')
                        ->placeholder('Walisantri.com')
                        ->maxLength(100),
                ]),
            Section::make('Jenis Email yang Dikirim')
                ->description('Kill-switch per jenis. Mematikan satu toggle menghentikan jenis email itu saja tanpa melumpuhkan kanalnya — berguna saat satu template bermasalah atau kuota harian Brevo menipis.')
                ->schema([
                    Toggle::make('email_sambutan_enabled')
                        ->label('Email sambutan untuk pesantren yang baru mendaftar')
                        ->default(true),
                    Toggle::make('email_reset_password_enabled')
                        ->label('Email tautan reset kata sandi')
                        ->helperText('Mematikan ini membuat admin & ustadz tidak bisa memulihkan akun sendiri — kata sandi harus direset manual lewat halaman Pengguna.')
                        ->default(true),
                    Toggle::make('email_invoice_enabled')
                        ->label('Email invoice saat order upgrade/perpanjangan dibuat')
                        ->default(true),
                    Toggle::make('email_pembayaran_enabled')
                        ->label('Email saat pembayaran order dikonfirmasi')
                        ->default(true),
                    Toggle::make('email_reminder_expired_enabled')
                        ->label('Email peringatan H-7/H-3 sebelum langganan berakhir')
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
                        Action::make('kirimUji')
                            ->label('Kirim Email Uji')
                            ->icon(Heroicon::OutlinedPaperAirplane)
                            ->color('gray')
                            ->requiresConfirmation()
                            ->modalHeading('Kirim email uji')
                            ->modalDescription(fn () => 'Email percobaan akan dikirim ke '.(auth()->user()?->email ?? 'alamat Anda').'. Simpan pengaturan lebih dulu bila baru saja diubah.')
                            ->action('kirimEmailUji'),
                    ])->key('form-actions'),
                ]),
        ]);
    }

    /**
     * Satu-satunya cara memverifikasi kredensial tanpa menunggu peristiwa nyata.
     *
     * Sengaja dikirim langsung (bukan lewat antrean): kalau kredensialnya salah,
     * kegagalannya harus terlihat sekarang di layar, bukan mengendap di
     * failed_jobs beberapa detik kemudian.
     */
    public function kirimEmailUji(): void
    {
        $penerima = auth()->user()?->email;

        if (blank($penerima)) {
            Notification::make()
                ->title('Akun Anda belum punya alamat email')
                ->body('Isi alamat email di profil Anda lebih dulu supaya email uji punya tujuan.')
                ->danger()
                ->send();

            return;
        }

        try {
            Mail::to($penerima)->send(new EmailUji);
        } catch (\Throwable $e) {
            Log::error('email_uji_gagal', ['message' => $e->getMessage()]);

            Notification::make()
                ->title('Email uji gagal dikirim')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        Notification::make()
            ->title("Email uji dikirim ke {$penerima}")
            ->body('Bila tidak muncul dalam beberapa menit, periksa folder spam dan pastikan SPF/DKIM domain sudah terpasang.')
            ->success()
            ->send();
    }

    public function save(): void
    {
        $state = $this->form->getState();

        try {
            foreach ([
                'email_sambutan_enabled',
                'email_reset_password_enabled',
                'email_invoice_enabled',
                'email_pembayaran_enabled',
                'email_reminder_expired_enabled',
            ] as $kunci) {
                EmailSetting::set($kunci, (bool) $state[$kunci]);
            }

            foreach (['smtp_host', 'smtp_port', 'smtp_scheme', 'smtp_username', 'from_address', 'from_name'] as $kunci) {
                filled($state[$kunci] ?? null)
                    ? EmailGatewaySetting::set($kunci, (string) $state[$kunci])
                    : EmailGatewaySetting::lupakan($kunci);
            }

            // Field kosong berarti "jangan diubah", bukan "kosongkan" — komponennya
            // di-dehydrate hanya saat diisi, jadi ketiadaan key sudah cukup jadi sinyal.
            if (isset($state['smtp_password'])) {
                EmailGatewaySetting::set('smtp_password', $state['smtp_password']);
            }
        } catch (\Throwable $e) {
            Log::error('email_settings_save_failed', ['message' => $e->getMessage()]);

            Notification::make()
                ->title('Gagal menyimpan pengaturan')
                ->body('Terjadi kesalahan saat menyimpan. Silakan coba lagi, atau hubungi admin bila berulang.')
                ->danger()
                ->send();

            return;
        }

        $this->refreshPasswordIndicator();

        Notification::make()
            ->title('Pengaturan email berhasil disimpan')
            ->success()
            ->send();
    }
}
