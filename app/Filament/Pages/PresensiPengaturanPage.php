<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Filament\Clusters\Presensi as ClusterPresensi;
use App\Filament\Resources\PresensiJamPelajarans\PresensiJamPelajaranResource;
use App\Filament\Support\ModulKomponen;
use App\Models\PresensiPengaturan;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
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

class PresensiPengaturanPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $cluster = ClusterPresensi::class;

    protected static ?string $title = 'Pengaturan Presensi';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'pengaturan-presensi';

    protected string $view = 'filament.pages.presensi-pengaturan-page';

    public ?string $jam_masuk = null;

    public ?int $toleransi_terlambat_menit = null;

    public array $hari_libur_mingguan = [];

    public ?int $batas_edit_ustadz_hari = null;

    /**
     * Nullable meski maknanya boolean.
     *
     * Properti Livewire bertipe `bool` polos pernah memecahkan render halaman ini:
     * state Toggle bisa kembali sebagai null (mis. saat komponennya disabled atau
     * belum terisi), dan "Cannot assign null to property of type bool" terjadi
     * SEBELUM satu pun pesan validasi sempat muncul.
     */
    public ?bool $presensi_per_jam_aktif = null;

    /**
     * Penomoran Carbon::dayOfWeek — 0 = Minggu, BUKAN ISO-8601 (1 = Senin … 7 = Minggu).
     * Salah membacanya tidak akan terlihat sampai ada pesantren yang liburnya bukan
     * Minggu, dan gejalanya cuma "hari efektif meleset satu hari" tanpa error apa pun.
     */
    public const HARI = [
        0 => 'Minggu',
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
    ];

    /**
     * Ikut mati bersama modul Presensi, dan itu memang benar: kalau modulnya
     * dimatikan, pengaturannya tidak punya alasan untuk tetap ada. Jalan kembalinya
     * lewat Manajemen → Modul, bukan lewat halaman ini.
     */
    public static function canAccess(): bool
    {
        return Auth::user()?->role === UserRole::AdminPesantren->value
            && ModulKomponen::aktif(static::class);
    }

    public function mount(): void
    {
        $pengaturan = PresensiPengaturan::untuk(Auth::user()->pesantren_id);

        $this->form->fill([
            'jam_masuk' => $pengaturan->jam_masuk,
            'toleransi_terlambat_menit' => $pengaturan->toleransi_terlambat_menit,
            'hari_libur_mingguan' => $pengaturan->hari_libur_mingguan ?? [],
            'batas_edit_ustadz_hari' => $pengaturan->batas_edit_ustadz_hari,
            'presensi_per_jam_aktif' => (bool) $pengaturan->presensi_per_jam_aktif,
        ]);
    }

    /**
     * Master jam pelajaran adalah Resource tersendiri, bukan Repeater di halaman ini.
     *
     * Alasannya bukan gaya: jam pelajaran punya riwayat — jam ke-8 yang dihapus
     * tahun ini tidak boleh menghapus catatan presensi jam ke-8 tahun lalu — dan
     * Repeater yang menyinkronkan seluruh koleksi tiap simpan adalah cara paling
     * mudah menghapus baris tanpa sengaja.
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('jamPelajaran')
                ->label('Atur Jam Pelajaran')
                ->icon(Heroicon::OutlinedClock)
                ->color('gray')
                ->url(PresensiJamPelajaranResource::getUrl())
                ->visible(fn (): bool => PresensiJamPelajaranResource::canViewAny()),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Jam & Keterlambatan')
                    ->description('Patokan untuk menentukan santri yang tercatat terlambat.')
                    ->columns(2)
                    ->schema([
                        TimePicker::make('jam_masuk')
                            ->label('Jam Masuk')
                            ->seconds(false)
                            ->required(),

                        TextInput::make('toleransi_terlambat_menit')
                            ->label('Toleransi Terlambat (menit)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(240)
                            ->required()
                            ->helperText('Santri yang tercatat setelah jam masuk + toleransi otomatis berstatus Terlambat.'),
                    ]),

                Section::make('Hari Libur Mingguan')
                    ->description('Hari yang memang tidak ada kegiatan. Hari ini tidak dihitung sebagai hari efektif di rekap.')
                    ->schema([
                        CheckboxList::make('hari_libur_mingguan')
                            ->hiddenLabel()
                            ->options(self::HARI)
                            ->columns(4),
                    ]),

                Section::make('Presensi per Jam Pelajaran')
                    ->description('Selain presensi harian, absen juga diambil tiap jam pelajaran oleh pengampu mapel.')
                    ->schema([
                        Toggle::make('presensi_per_jam_aktif')
                            ->label('Aktifkan presensi per jam pelajaran')
                            ->helperText('Saat aktif, muncul halaman Isi Presensi per Jam. Presensi harian tetap berjalan seperti biasa — keduanya tersimpan terpisah dan tidak saling menimpa. Atur pembagian jamnya lewat tombol "Atur Jam Pelajaran" di atas.'),
                    ]),

                Section::make('Batas Edit')
                    ->description('Menjaga catatan lama tidak diubah diam-diam — wali santri membacanya.')
                    ->schema([
                        TextInput::make('batas_edit_ustadz_hari')
                            ->label('Batas Edit Ustadz (hari)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(365)
                            ->required()
                            ->helperText('Berapa hari ke belakang ustadz masih boleh mengisi/mengubah presensi. Isi 0 untuk tanpa batas. Admin pesantren tidak pernah terkena batas ini.'),
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
        $data = $this->form->getState();

        PresensiPengaturan::untuk(Auth::user()->pesantren_id)->update([
            'jam_masuk' => $data['jam_masuk'],
            'toleransi_terlambat_menit' => $data['toleransi_terlambat_menit'],
            // Nilai checkbox datang sebagai string; disimpan sebagai integer supaya
            // perbandingan dengan Carbon::dayOfWeek nanti tidak bergantung tipe.
            'hari_libur_mingguan' => array_values(array_map('intval', $data['hari_libur_mingguan'] ?? [])),
            'batas_edit_ustadz_hari' => $data['batas_edit_ustadz_hari'],
            'presensi_per_jam_aktif' => (bool) ($data['presensi_per_jam_aktif'] ?? false),
        ]);

        Notification::make()
            ->title('Pengaturan presensi tersimpan.')
            ->success()
            ->send();
    }
}
