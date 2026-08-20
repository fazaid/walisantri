<?php

namespace App\Filament\Pages;

use App\Enums\StatusKehadiran;
use App\Enums\SumberPresensi;
use App\Enums\UserRole;
use App\Filament\Clusters\Presensi as ClusterPresensi;
use App\Filament\Support\ModulKomponen;
use App\Models\Kelas;
use App\Models\Presensi;
use App\Models\PresensiPengaturan;
use App\Models\Santri;
use App\Services\PresensiKalender;
use App\Support\PenugasanUstadz;
use App\Support\Waktu;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PresensiHarianPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $cluster = ClusterPresensi::class;

    protected static ?string $navigationLabel = 'Isi Presensi';

    protected static ?string $title = 'Isi Presensi Harian';

    /**
     * Menu Presensi sengaja hanya berisi empat submenu (Kehadiran · Rekap ·
     * Hari Libur · Pengajuan Izin); halaman ini dicapai lewat tombol di header
     * ListPresensis. canAccess() tetap menjaga akses URL langsung.
     */
    protected static bool $shouldRegisterNavigation = false;

    /**
     * BUKAN 'isi-harian' — nama itu sudah dipakai MutabaahHarianPage, dan nama
     * route Filament diturunkan dari slug.
     */
    protected static ?string $slug = 'isi-presensi';

    protected string $view = 'filament.pages.presensi-harian-page';

    public const KELOMPOK_KELAS = 'kelas';

    public const KELOMPOK_SEMUA = 'semua';

    public const KELOMPOK_TANPA_KELAS = 'tanpa_kelas';

    public ?string $tanggal = null;

    public ?string $kelompok = self::KELOMPOK_KELAS;

    public ?string $kelas_id = null;

    public array $rows = [];

    protected ?PresensiPengaturan $pengaturan = null;

    public static function canAccess(): bool
    {
        return in_array(Auth::user()?->role, [
            UserRole::AdminPesantren->value,
            UserRole::Ustadz->value,
        ], true)
            && ModulKomponen::aktif(static::class);
    }

    protected function pengaturan(): PresensiPengaturan
    {
        return $this->pengaturan ??= PresensiPengaturan::untuk(Auth::user()->pesantren_id);
    }

    protected function isUstadz(): bool
    {
        return Auth::user()?->role === UserRole::Ustadz->value;
    }

    public function mount(): void
    {
        // WIB, bukan UTC — mengisi presensi subuh (00.00–07.00 WIB) tidak boleh
        // jatuh ke tanggal kemarin.
        $tanggal = Waktu::hariIni();
        $kelasId = array_key_first($this->kelasOptions());

        $this->form->fill([
            'tanggal' => $tanggal,
            'kelompok' => self::KELOMPOK_KELAS,
            'kelas_id' => $kelasId ? (string) $kelasId : null,
            'rows' => $this->buildRows($tanggal, self::KELOMPOK_KELAS, $kelasId),
        ]);
    }

    /** Kelas yang boleh diisi pengguna ini — ustadz dibatasi kelas perwaliannya. */
    protected function kelasOptions(): array
    {
        $query = Kelas::query();

        if ($this->isUstadz()) {
            $query->whereIn('id', PenugasanUstadz::kelasIdsPerwalian());
        }

        return $query->orderBy('nama_kelas')->pluck('nama_kelas', 'id')->all();
    }

    protected function jumlahTanpaKelas(): int
    {
        return Santri::where('status_aktif', true)->whereNull('kelas_id')->count();
    }

    /**
     * Query santri untuk kelompok terpilih.
     *
     * Mode selain KELAS hanya untuk admin — dijaga di sini juga, bukan cuma di
     * visible() form, supaya request Livewire yang dirakit tangan tidak bisa
     * memilih mode yang tidak boleh ia lihat.
     */
    protected function getSantriQuery(string $kelompok, ?int $kelasId): Builder
    {
        $query = Santri::where('status_aktif', true);

        if ($this->isUstadz()) {
            return $query
                ->whereIn('kelas_id', PenugasanUstadz::kelasIdsPerwalian())
                ->when($kelasId, fn (Builder $q) => $q->where('kelas_id', $kelasId));
        }

        return match ($kelompok) {
            self::KELOMPOK_SEMUA => $query,
            self::KELOMPOK_TANPA_KELAS => $query->whereNull('kelas_id'),
            default => $query->where('kelas_id', $kelasId),
        };
    }

    protected function buildRows(?string $tanggal, ?string $kelompok, int|string|null $kelasId): array
    {
        if (! $tanggal || ! $kelompok) {
            return [];
        }

        if ($kelompok === self::KELOMPOK_KELAS && ! $kelasId) {
            return [];
        }

        $santriList = $this->getSantriQuery($kelompok, $kelasId ? (int) $kelasId : null)
            ->orderBy('nama_lengkap')
            ->get(['id', 'nama_lengkap', 'kelas_id']);

        $existing = Presensi::whereDate('tanggal', $tanggal)
            ->where('jam_ke', Presensi::HARIAN)
            ->whereIn('santri_id', $santriList->pluck('id'))
            ->get()
            ->keyBy('santri_id');

        return $santriList->map(function (Santri $santri) use ($existing) {
            $rec = $existing->get($santri->id);

            return [
                'santri_id' => $santri->id,
                'nama' => $santri->nama_lengkap,
                'kelas_id' => $santri->kelas_id,
                // Di-prefill Hadir, bukan kosong. Menekan simpan berarti hari itu
                // DITUTUP OLEH MANUSIA — itulah yang membuat status Alpa yang
                // tersimpan selalu berarti "seseorang menyatakannya", bukan
                // "sistem menebak" (§11: tidak ada job penanda Alpa otomatis).
                'status' => $rec?->status?->value ?? StatusKehadiran::Hadir->value,
                'catatan' => $rec?->catatan,
            ];
        })->values()->toArray();
    }

    /**
     * Kenapa halaman ini tidak bisa dipakai, kalau memang tidak bisa — dibaca view
     * sebelum form dirender. Keduanya perlu tindakan orang lain, jadi pesannya
     * menyebut langkah konkret, bukan sekadar "data kosong".
     *
     * @return array{judul: string, saran: string}|null
     */
    public function peringatanKosong(): ?array
    {
        if ($this->isUstadz() && PenugasanUstadz::kelasIdsPerwalian()->isEmpty()) {
            return [
                'judul' => 'Anda belum ditetapkan sebagai wali kelas.',
                'saran' => 'Presensi harian diisi oleh wali kelas. Minta admin pesantren menetapkan Anda lewat menu Santri → Kelas.',
            ];
        }

        if (! Santri::where('status_aktif', true)->exists()) {
            return [
                'judul' => 'Belum ada santri aktif yang bisa diabsen.',
                'saran' => Auth::user()?->role === UserRole::AdminPesantren->value
                    ? 'Tambahkan santri lebih dulu lewat menu Santri.'
                    : 'Minta admin pesantren menambahkan data santri lebih dulu.',
            ];
        }

        return null;
    }

    /**
     * Keterangan hari libur untuk tanggal yang sedang dipilih, atau null.
     *
     * MEMPERINGATKAN, bukan melarang: ada pondok yang tetap berkegiatan di hari
     * libur (kajian, kerja bakti, lomba), dan melarang pengisian akan memaksa
     * mereka memakai tanggal yang salah. Yang dicegah peringatan ini adalah
     * kesalahan yang jauh lebih umum — salah memilih tanggal.
     */
    public function peringatanHariLibur(): ?string
    {
        $tanggal = $this->tanggal ?? Waktu::hariIni();

        return PresensiKalender::untuk(Auth::user()->pesantren_id)
            ->keteranganLibur($tanggal);
    }

    public function form(Schema $schema): Schema
    {
        $pengaturan = $this->pengaturan();
        $batasAwal = $this->isUstadz() ? $pengaturan->batasAwalEditUstadz() : null;
        $jumlahTanpaKelas = $this->jumlahTanpaKelas();

        return $schema
            ->components([
                DatePicker::make('tanggal')
                    ->label('Tanggal')
                    ->required()
                    ->maxDate(Waktu::akhirHariIni())
                    // Lapis pertama jendela edit. Lapis keduanya di save() — minDate
                    // hanyalah validasi form yang bisa dilewati request buatan tangan.
                    ->minDate($batasAwal)
                    ->helperText($batasAwal
                        ? 'Anda dapat mengisi/mengubah presensi maksimal '.$pengaturan->batas_edit_ustadz_hari.' hari ke belakang.'
                        : null)
                    ->native(false)
                    ->closeOnDateSelection()
                    ->live()
                    ->afterStateUpdated(fn (Get $get, callable $set) => $this->refreshRows($get, $set)),

                Select::make('kelompok')
                    ->label('Kelompok')
                    ->options(fn () => $this->kelompokOptions($jumlahTanpaKelas))
                    ->default(self::KELOMPOK_KELAS)
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Get $get, callable $set) => $this->refreshRows($get, $set)),

                Select::make('kelas_id')
                    ->label('Kelas')
                    ->options(fn () => $this->kelasOptions())
                    ->visible(fn (Get $get) => $get('kelompok') === self::KELOMPOK_KELAS)
                    ->required(fn (Get $get) => $get('kelompok') === self::KELOMPOK_KELAS)
                    ->live()
                    ->afterStateUpdated(fn (Get $get, callable $set) => $this->refreshRows($get, $set)),

                Repeater::make('rows')
                    ->hiddenLabel()
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->itemLabel(fn (array $state): ?string => $state['nama'] ?? null)
                    ->schema([
                        Hidden::make('santri_id'),
                        Hidden::make('nama'),
                        Hidden::make('kelas_id'),

                        Select::make('status')
                            ->label('Status')
                            ->options(StatusKehadiran::options())
                            ->default(StatusKehadiran::Hadir->value)
                            ->required(),

                        TextInput::make('catatan')
                            ->label('Catatan')
                            ->maxLength(255),
                    ])
                    ->columns(['default' => 1, 'md' => 2]),
            ]);
    }

    /** @return array<string, string> */
    protected function kelompokOptions(int $jumlahTanpaKelas): array
    {
        $opsi = [self::KELOMPOK_KELAS => 'Per kelas'];

        // Dua mode di bawah khusus admin. Ustadz cakupannya memang kelas perwalian,
        // jadi "semua santri" tidak punya arti baginya.
        if (! $this->isUstadz()) {
            $opsi[self::KELOMPOK_SEMUA] = 'Semua santri aktif';
            $opsi[self::KELOMPOK_TANPA_KELAS] = "Belum punya kelas ({$jumlahTanpaKelas} santri)";
        }

        return $opsi;
    }

    protected function refreshRows(Get $get, callable $set): void
    {
        $set('rows', $this->buildRows(
            $get('tanggal'),
            $get('kelompok'),
            $get('kelas_id'),
        ));
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
                            ->label('Simpan Presensi')
                            ->submit('save'),
                    ])->key('form-actions'),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $rows = $data['rows'] ?? [];

        if ($rows === []) {
            Notification::make()
                ->title('Tidak ada santri untuk disimpan')
                ->body('Pilih kelompok yang berisi santri lebih dulu.')
                ->warning()
                ->send();

            return;
        }

        // Lapis KEDUA jendela edit — wajib. minDate di DatePicker hanya menjaga UI;
        // request Livewire yang dirakit tangan bisa mengirim tanggal apa pun.
        if (! $this->tanggalDalamJendelaEdit($data['tanggal'])) {
            Notification::make()
                ->title('Tanggal di luar batas edit')
                ->body('Anda hanya dapat mengisi presensi maksimal '.$this->pengaturan()->batas_edit_ustadz_hari.' hari ke belakang. Hubungi admin pesantren untuk memperbaiki data yang lebih lama.')
                ->danger()
                ->send();

            return;
        }

        // santri_id dan kelas_id dari klien tidak dipercaya. Repeater mengirim
        // balik apa pun yang ada di state-nya, dan request Livewire yang dirakit
        // tangan bisa memuat santri di luar kelompok yang sedang dipilih — bahkan
        // di luar cakupan perwalian ustadz. Yang menentukan siapa yang boleh
        // ditulis adalah query yang sama dengan pembangun barisnya.
        // ?? null, bukan akses langsung: Select 'kelas_id' bersembunyi di mode
        // "Semua santri" dan "Belum punya kelas", dan komponen tersembunyi memang
        // TIDAK ikut di state form.
        $santriSah = $this->getSantriQuery(
            $data['kelompok'],
            ($data['kelas_id'] ?? null) ? (int) $data['kelas_id'] : null,
        )->pluck('kelas_id', 'id');

        $rows = array_values(array_filter(
            $rows,
            fn (array $row): bool => $santriSah->has((int) $row['santri_id']),
        ));

        if ($rows === []) {
            Notification::make()
                ->title('Tidak ada santri untuk disimpan')
                ->body('Pilih kelompok yang berisi santri lebih dulu.')
                ->warning()
                ->send();

            return;
        }

        $pesantrenId = Auth::user()->pesantren_id;
        $sekarang = now();

        $baris = array_map(fn (array $row): array => [
            'pesantren_id' => $pesantrenId,
            'santri_id' => $row['santri_id'],
            'tanggal' => $data['tanggal'],
            'jam_ke' => Presensi::HARIAN,
            // Snapshot kelas saat dicatat — bukan dibaca ulang saat rekap, dan
            // bukan pula diambil dari kiriman klien.
            'kelas_id' => $santriSah->get((int) $row['santri_id']),
            'status' => $row['status'],
            'catatan' => $row['catatan'] ?? null,
            'sumber' => SumberPresensi::Manual->value,
            'dicatat_oleh' => Auth::id(),
            'dicatat_at' => $sekarang,
            'created_at' => $sekarang,
            'updated_at' => $sekarang,
        ], $rows);

        try {
            // upsert(), bukan loop updateOrCreate() — lihat pelajaran v4.27: yang
            // terakhir adalah SELECT-lalu-INSERT, dan satu tabrakan di dalam
            // transaksi membatalkan penyimpanan SELURUH batch.
            //
            // Tetap dibungkus DB::transaction meski isinya satu pernyataan: di
            // PostgreSQL, pernyataan yang gagal membuat seluruh transaksi berjalan
            // jadi aborted (25P02), jadi pembungkus ini menjadikannya savepoint saat
            // save() dipanggil di dalam transaksi lain (termasuk RefreshDatabase).
            //
            // upsert melewati cast DAN model event, jadi pesantren_id yang biasanya
            // diisi Multitenantable::creating() disetel manual di atas.
            DB::transaction(fn () => Presensi::upsert(
                $baris,
                ['santri_id', 'tanggal', 'jam_ke'],
                ['status', 'catatan', 'kelas_id', 'sumber', 'dicatat_oleh', 'dicatat_at', 'updated_at'],
            ));
        } catch (\Throwable $e) {
            Log::error('presensi_harian_save_failed', ['message' => $e->getMessage()]);

            Notification::make()
                ->title('Gagal menyimpan presensi')
                ->body('Terjadi kesalahan saat menyimpan data. Silakan coba lagi, atau hubungi admin bila berulang.')
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Presensi tersimpan untuk '.count($rows).' santri.')
            ->success()
            ->send();
    }

    /**
     * Publik supaya bisa diuji langsung.
     *
     * Lewat UI, penolakan tanggal lampau hampir selalu ditangkap lebih dulu oleh
     * ->minDate() di DatePicker, sehingga jalur ini tidak pernah tersentuh dari
     * form — dan justru itulah alasannya ada: ia menjaga request Livewire yang
     * dirakit tangan, yang tidak pernah melewati validasi form sama sekali.
     * Kalau ia hanya bisa dicapai lewat form, ia tidak menjaga apa pun.
     */
    public function tanggalDalamJendelaEdit(?string $tanggal): bool
    {
        if (! $this->isUstadz()) {
            return true;
        }

        $batasAwal = $this->pengaturan()->batasAwalEditUstadz();

        return $batasAwal === null || $tanggal >= $batasAwal;
    }
}
