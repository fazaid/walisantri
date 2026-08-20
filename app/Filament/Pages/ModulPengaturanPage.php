<?php

namespace App\Filament\Pages;

use App\Enums\Modul;
use App\Enums\NavigationGroup;
use App\Enums\UserRole;
use App\Models\ModulPengaturan;
use App\Observers\ActivityLogger;
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
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Pesantren memilih sendiri modul mana yang dipakai.
 *
 * Modul yang dimatikan hilang dari menu SEMUA orang di pesantren itu — admin,
 * ustadz, dan wali santri — bukan per pengguna. Itu memang inti rancangannya:
 * menu yang mengikuti penugasan per-orang berubah sendiri saat penugasan berpindah,
 * dan "menu saya kok beda dengan teman saya" jauh lebih sulit didiagnosis lewat
 * WhatsApp daripada daftar kosong yang menjelaskan dirinya sendiri.
 *
 * ⚠️ Halaman ini TIDAK PERNAH ikut dimatikan modul. Ia aman by construction —
 * ModulKomponen::modul() mengembalikan null untuk komponen tanpa cluster — dan
 * justru karena keamanannya tidak terlihat di kode, ia ditulis di sini: kalau
 * halaman ini ikut hilang, admin yang mematikan seluruh modul kehilangan satu-satunya
 * jalan kembali.
 */
class ModulPengaturanPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Manajemen;

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Modul';

    protected static ?string $title = 'Pengaturan Modul';

    protected static ?string $slug = 'pengaturan-modul';

    protected string $view = 'filament.pages.modul-pengaturan-page';

    /**
     * Keenamnya nullable meski maknanya boolean.
     *
     * Properti Livewire bertipe `bool` polos pernah memecahkan render halaman
     * Pengaturan Presensi: state Toggle bisa kembali sebagai null, dan "Cannot
     * assign null to property of type bool" terjadi SEBELUM satu pun pesan validasi
     * sempat muncul (changelog v4.28/v4.39).
     */
    public ?bool $akademik_aktif = null;

    public ?bool $tahfidz_aktif = null;

    public ?bool $presensi_aktif = null;

    public ?bool $kesantrian_aktif = null;

    public ?bool $keuangan_aktif = null;

    public ?bool $rapor_aktif = null;

    public static function canAccess(): bool
    {
        return Auth::user()?->role === UserRole::AdminPesantren->value;
    }

    public function mount(): void
    {
        $pengaturan = ModulPengaturan::untuk(Auth::user()->pesantren_id);

        $this->form->fill(
            collect(Modul::cases())
                ->mapWithKeys(fn (Modul $modul) => [
                    $modul->kolom() => (bool) $pengaturan->{$modul->kolom()},
                ])
                ->all()
        );
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Modul yang Aktif')
                    ->description('Modul yang dimatikan hilang dari menu semua pengguna di pesantren ini — admin, ustadz, maupun wali santri. Datanya tidak dihapus: mematikan modul SPP tidak menghapus satu pun tagihan, dan semuanya kembali utuh saat modul dinyalakan lagi.')
                    ->columns(2)
                    ->schema(
                        collect(Modul::cases())
                            ->map(fn (Modul $modul) => Toggle::make($modul->kolom())
                                ->label($modul->label())
                                ->helperText($modul->penjelasan()))
                            ->all()
                    ),
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
        $data = $this->form->getState();

        $pengaturan = ModulPengaturan::untuk(Auth::user()->pesantren_id);

        $sebelum = [];
        $sesudah = [];

        foreach (Modul::cases() as $modul) {
            $kolom = $modul->kolom();
            $sebelum[$kolom] = (bool) $pengaturan->{$kolom};
            $sesudah[$kolom] = (bool) ($data[$kolom] ?? false);
        }

        $pengaturan->update($sesudah);

        // Saat seorang ustadz melapor "menu saya hilang", jejak ini satu-satunya
        // jawaban — tidak ada permukaan lain yang mencatat siapa mematikan apa.
        ActivityLogger::log('modul.diubah', $pengaturan, $sebelum, $sesudah);

        Notification::make()
            ->title('Pengaturan modul tersimpan.')
            ->success()
            ->send();

        // ⚠️ Muat ulang halaman penuh, bukan sekadar render ulang komponen.
        //
        // Sidebar, tab cluster, dan bottom-nav HP dirender di layout halaman —
        // DI LUAR komponen Livewire ini. Tanpa redirect, save() hanya me-render
        // ulang formnya: toggle-nya berubah, notifikasinya muncul, tapi menu yang
        // baru saja dimatikan tetap terpampang sampai admin menekan refresh
        // sendiri. Gejalanya terbaca sebagai "toggle-nya tidak bekerja", padahal
        // datanya sudah benar tersimpan.
        //
        // Notification di atas selamat karena Notification::send() menulis ke
        // session, bukan mengirim event browser (vendor/filament/notifications).
        //
        // Halaman ini tidak pernah ikut dimatikan modul, jadi sasaran redirect-nya
        // tidak mungkin berbalik jadi 403.
        $this->redirect(static::getUrl());
    }
}
