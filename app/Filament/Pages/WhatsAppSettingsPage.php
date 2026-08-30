<?php

namespace App\Filament\Pages;

use App\Enums\NavigationGroup;
use App\Enums\UserRole;
use App\Models\PlatformContactSetting;
use App\Models\WhatsAppGatewaySetting;
use App\Models\WhatsAppMessageTemplate;
use App\Models\WhatsAppSetting;
use App\Services\FonnteWhatsAppService;
use App\Services\NotifikasiAdminPlatform;
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
use Illuminate\Support\Facades\Log;
use UnitEnum;

class WhatsAppSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::PengaturanPlatform;

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'WhatsApp';

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

    public ?string $cs_whatsapp = null;

    public string $cs_bantuan_template = '';

    public bool $notif_admin_platform_enabled = false;

    public ?string $admin_whatsapp = null;

    public string $notif_admin_demo_baru_template = '';

    public string $notif_admin_order_baru_template = '';

    public string $notif_admin_order_bukti_template = '';

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
            'cs_whatsapp' => PlatformContactSetting::csWhatsapp(),
            'cs_bantuan_template' => WhatsAppMessageTemplate::get(
                'cs_invoice_bantuan',
                OrderInvoicePage::DEFAULT_CS_BANTUAN_TEMPLATE,
            ),
            // Default `false` eksplisit — WhatsAppSetting::get() default-nya true.
            'notif_admin_platform_enabled' => WhatsAppSetting::get('notif_admin_platform_enabled', false),
            'admin_whatsapp' => PlatformContactSetting::adminWhatsapp(),
            'notif_admin_demo_baru_template' => WhatsAppMessageTemplate::get(
                'notif_admin_demo_baru',
                NotifikasiAdminPlatform::DEFAULT_DEMO_BARU,
            ),
            'notif_admin_order_baru_template' => WhatsAppMessageTemplate::get(
                'notif_admin_order_baru',
                NotifikasiAdminPlatform::DEFAULT_ORDER_BARU,
            ),
            'notif_admin_order_bukti_template' => WhatsAppMessageTemplate::get(
                'notif_admin_order_bukti',
                NotifikasiAdminPlatform::DEFAULT_ORDER_BUKTI,
            ),
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
            Section::make('Notifikasi Internal ke Admin Platform')
                ->description('Alert ke nomor WhatsApp Anda sendiri saat ada lead demo atau pesanan upgrade masuk. Kategori BERBEDA dari empat notifikasi di bawah: penerimanya bukan pelanggan, melainkan satu nomor tetap milik pemilik platform. Karena itu kill-switch-nya terpisah dan default MATI.')
                ->schema([
                    TextInput::make('admin_whatsapp')
                        ->label('Nomor WhatsApp admin platform')
                        ->placeholder('081399096658')
                        ->maxLength(20)
                        ->helperText('Tujuan semua alert internal. Boleh ditulis 08xx maupun 62xx — akan disimpan dalam format internasional. Kosongkan untuk menghentikan pengiriman tanpa mematikan toggle.')
                        ->rule($this->nomorWhatsappRule()),
                    Toggle::make('notif_admin_platform_enabled')
                        ->label('Kirim alert WhatsApp saat lead demo & pesanan upgrade masuk')
                        ->helperText('Default mati. Nyalakan hanya setelah memastikan token Fonnte hidup lewat tombol "Kirim WA Tes" di bawah.')
                        ->default(false),
                    Actions::make([
                        Action::make('kirim_wa_tes')
                            ->label('Kirim WA Tes')
                            ->icon(Heroicon::OutlinedPaperAirplane)
                            ->color('gray')
                            ->action('kirimWaTes'),
                    ])->key('aksi-wa-tes'),
                    Textarea::make('notif_admin_demo_baru_template')
                        ->label('Template alert lead demo baru')
                        ->required()
                        ->rows(8)
                        ->helperText('Placeholder yang bisa dipakai: {nama_pesantren}, {nama_kontak}, {no_hp}, {kota}, {jumlah_santri}, {link_admin}.'),
                    Textarea::make('notif_admin_order_baru_template')
                        ->label('Template alert pesanan upgrade dibuat')
                        ->required()
                        ->rows(8)
                        ->helperText('Dikirim saat pesantren membuat order (status menunggu pembayaran). Placeholder: {nama_pesantren}, {nomor_order}, {nomor_invoice}, {paket}, {durasi_bulan}, {total}, {link_admin}.'),
                    Textarea::make('notif_admin_order_bukti_template')
                        ->label('Template alert bukti transfer masuk')
                        ->required()
                        ->rows(8)
                        ->helperText('Dikirim saat pesantren mengunggah bukti transfer — inilah momen yang menunggu konfirmasi Anda. Placeholder sama dengan template di atas.'),
                ]),
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
            Section::make('Kontak CS')
                ->description('Nomor WhatsApp yang dihubungi pesantren saat butuh bantuan. Berbeda dari gateway di atas: ini chat manual, tidak mengirim pesan otomatis dan tidak memakai kuota Fonnte.')
                ->schema([
                    TextInput::make('cs_whatsapp')
                        ->label('Nomor WhatsApp CS')
                        ->placeholder('081399096658')
                        ->maxLength(20)
                        ->helperText('Ditampilkan sebagai tombol "Hubungi CS" di halaman invoice pesantren. Boleh ditulis 08xx maupun 62xx — akan disimpan dalam format internasional. Kosongkan untuk menyembunyikan tombol.')
                        ->rule($this->nomorWhatsappRule()),
                    Textarea::make('cs_bantuan_template')
                        ->label('Template pesan awal')
                        ->required()
                        ->rows(4)
                        ->helperText('Teks yang sudah terketik otomatis di kolom chat saat pesantren menekan tombol "Hubungi CS". Placeholder yang bisa dipakai: {nomor_invoice}, {nomor_order}, {nama_pesantren}, {total}, {status_order}.'),
                ]),
        ]);
    }

    // Dipakai dua field nomor (CS & admin platform): kosong boleh, tapi kalau diisi
    // harus lolos normalisasi Fonnte supaya tidak tersimpan dalam bentuk yang pasti
    // ditolak gateway saat kirim.
    //
    // Closure BERLAPIS dan itu wajib: Filament mengevaluasi closure terluar lewat
    // container-nya sendiri, lalu memakai nilai kembaliannya sebagai rule Laravel.
    // Menyerahkan closure validasi langsung membuat Filament mencoba me-resolve
    // $attribute sebagai dependency dan gagal.
    private function nomorWhatsappRule(): \Closure
    {
        return static function (): \Closure {
            return static function (string $attribute, mixed $value, \Closure $fail): void {
                if (blank($value)) {
                    return;
                }

                if (app(FonnteWhatsAppService::class)->normalizePhoneNumber((string) $value) === null) {
                    $fail('Nomor WhatsApp tidak valid. Contoh yang benar: 081399096658.');
                }
            };
        };
    }

    // Sengaja SINKRON (bukan lewat KirimNotifikasiWhatsapp), mengikuti preseden
    // EmailUji: tes kredensial harus gagal dengan keras di layar, bukan diam-diam
    // masuk failed_jobs. Tombol ini juga sengaja mengabaikan kill-switch supaya
    // token & nomor bisa diverifikasi sebelum notifikasinya dinyalakan.
    public function kirimWaTes(): void
    {
        $nomor = app(FonnteWhatsAppService::class)->normalizePhoneNumber((string) $this->admin_whatsapp);

        if ($nomor === null) {
            Notification::make()
                ->title('Nomor WhatsApp admin belum diisi atau tidak valid')
                ->body('Isi nomor yang benar di kolom di atas lebih dulu, contoh: 081399096658.')
                ->warning()
                ->send();

            return;
        }

        try {
            app(FonnteWhatsAppService::class)->send(
                $nomor,
                'Tes koneksi WhatsApp Walisantri.com. Jika pesan ini masuk, gateway Fonnte dan nomor admin platform sudah benar.',
            );
        } catch (\Throwable $e) {
            Log::error('whatsapp_tes_admin_gagal', ['message' => $e->getMessage()]);

            Notification::make()
                ->title('Gagal mengirim WA tes')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('WA tes terkirim ke '.$nomor)
            ->success()
            ->send();
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

            // Disimpan ternormalisasi (0813... -> 62813...) supaya konsumen cukup
            // menempelkannya ke https://wa.me/{nomor} tanpa memproses ulang.
            PlatformContactSetting::set(
                'cs_whatsapp',
                filled($state['cs_whatsapp'])
                    ? app(FonnteWhatsAppService::class)->normalizePhoneNumber($state['cs_whatsapp'])
                    : null,
            );
            WhatsAppMessageTemplate::set('cs_invoice_bantuan', $state['cs_bantuan_template']);

            WhatsAppSetting::set('notif_admin_platform_enabled', (bool) $state['notif_admin_platform_enabled']);
            WhatsAppMessageTemplate::set('notif_admin_demo_baru', $state['notif_admin_demo_baru_template']);
            WhatsAppMessageTemplate::set('notif_admin_order_baru', $state['notif_admin_order_baru_template']);
            WhatsAppMessageTemplate::set('notif_admin_order_bukti', $state['notif_admin_order_bukti_template']);

            // Ternormalisasi seperti cs_whatsapp — NotifikasiAdminPlatform meneruskan
            // nilai ini apa adanya ke job pengirim.
            PlatformContactSetting::set(
                'admin_whatsapp',
                filled($state['admin_whatsapp'])
                    ? app(FonnteWhatsAppService::class)->normalizePhoneNumber($state['admin_whatsapp'])
                    : null,
            );
        } catch (\Throwable $e) {
            Log::error('whatsapp_settings_save_failed', ['message' => $e->getMessage()]);

            Notification::make()
                ->title('Gagal menyimpan pengaturan')
                ->body('Terjadi kesalahan saat menyimpan. Silakan coba lagi, atau hubungi admin bila berulang.')
                ->danger()
                ->send();

            return;
        }

        $this->refreshFonnteTokenIndicator();

        Notification::make()
            ->title('Pengaturan WhatsApp berhasil disimpan')
            ->success()
            ->send();
    }
}
