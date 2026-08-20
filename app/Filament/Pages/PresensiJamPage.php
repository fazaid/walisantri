<?php

namespace App\Filament\Pages;

use App\Enums\StatusKehadiran;
use App\Enums\SumberPresensi;
use App\Enums\UserRole;
use App\Filament\Clusters\Presensi as ClusterPresensi;
use App\Filament\Resources\PresensiJamPelajarans\PresensiJamPelajaranResource;
use App\Filament\Support\ModulKomponen;
use App\Models\MataPelajaran;
use App\Models\Presensi;
use App\Models\PresensiJamPelajaran;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Isi presensi satu jam pelajaran — mode opsional, mati secara bawaan.
 *
 * Berbeda dari presensi harian yang dipegang wali kelas, presensi per jam
 * dipegang PENGAMPU MAPEL: yang berdiri di depan kelas pada jam itu adalah dia,
 * bukan wali kelasnya. Karena itu pemilihannya dimulai dari MATA PELAJARAN, dan
 * kelasnya diturunkan dari situ (`mata_pelajaran.kelas_id` NOT NULL) — pola yang
 * sama dengan NilaiMassalPage.
 *
 * Baris yang ditulis memakai `jam_ke > 0`, jadi ia tidak pernah menabrak presensi
 * harian santri yang sama: unique-nya `(santri_id, tanggal, jam_ke)`.
 */
class PresensiJamPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $cluster = ClusterPresensi::class;

    protected static ?string $title = 'Isi Presensi per Jam Pelajaran';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'isi-presensi-jam';

    protected string $view = 'filament.pages.presensi-jam-page';

    public ?string $tanggal = null;

    public ?string $mata_pelajaran_id = null;

    public ?string $jam_ke = null;

    public array $rows = [];

    protected ?PresensiPengaturan $pengaturan = null;

    /**
     * Peran + modul Presensi. TIDAK termasuk cek toggle `presensi_per_jam_aktif`.
     *
     * Kalau toggle per-jam ikut dinilai di sini, admin yang membuka URL-nya saat
     * fitur mati akan menabrak 403 telanjang — padahal dialah satu-satunya orang
     * yang bisa menyalakannya, dan yang ia butuhkan justru penjelasan plus
     * tautannya. Penjagaan fitur itu ada di peringatanKosong() (untuk layar) dan
     * di save() (untuk request yang dirakit tangan).
     *
     * ⚠️ Toggle MODUL (v4.57) beda perkara, dan bedanya mekanis bukan selera:
     * canAccess() adalah metode yang SAMA yang memutuskan "menu tampil" — Filament
     * membangun navigasi cluster dari sini (Cluster::canAccessClusteredComponents()).
     * Tidak mungkin membiarkan URL terbuka sambil membuat menunya hilang; keduanya
     * satu saklar. Preseden di atas justru bekerja karena toggle per-jam hidup DI
     * DALAM modul yang menunya tetap ada.
     */
    public static function canAccess(): bool
    {
        return in_array(Auth::user()?->role, [
            UserRole::AdminPesantren->value,
            UserRole::Ustadz->value,
        ], true)
            && ModulKomponen::aktif(static::class);
    }

    /** Dipakai tombol header di ListPresensis — tombolnya sembunyi saat fitur mati. */
    public static function aktifUntukPengguna(): bool
    {
        return static::canAccess()
            && PresensiPengaturan::untuk(Auth::user()->pesantren_id)->presensi_per_jam_aktif;
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
        $tanggal = Waktu::hariIni();
        $mapelId = array_key_first($this->mapelOptions());
        $jamKe = array_key_first($this->jamOptions());

        $this->form->fill([
            'tanggal' => $tanggal,
            'mata_pelajaran_id' => $mapelId ? (string) $mapelId : null,
            'jam_ke' => $jamKe ? (string) $jamKe : null,
            'rows' => $this->buildRows($tanggal, $mapelId, $jamKe),
        ]);
    }

    /**
     * Mapel yang boleh diisi pengguna ini.
     *
     * Ustadz dibatasi mapel yang ia ampu — bukan kelas yang ia walikan. Seorang
     * wali kelas 3A yang tidak mengampu satu pun mapel tetap tidak bisa mengisi
     * presensi jam pelajaran 3A, dan itu memang aturannya (§5.4): penugasan di
     * satu jalur tidak membuka jalur lain.
     *
     * Publik supaya daftar cakupannya bisa diperiksa tes secara langsung.
     *
     * @return array<int, string>
     */
    public function mapelOptions(): array
    {
        $query = MataPelajaran::query()->with('kelas');

        if ($this->isUstadz()) {
            $query->whereIn('id', PenugasanUstadz::mataPelajaranIdsDiampu());
        }

        // Diurutkan di PHP, bukan lewat join ke `kelas`. Join-nya sempat ada dan
        // langsung pecah: `whereIn('id', ...)` milik penyaringan ustadz jadi
        // ambigu begitu dua tabel sama-sama punya kolom `id`. Daftar mapel satu
        // pesantren cukup pendek untuk diurutkan di memori.
        return $query
            ->get()
            ->sortBy(fn (MataPelajaran $m): string => ($m->kelas?->nama_kelas ?? '').' '.$m->nama_mapel)
            ->mapWithKeys(fn (MataPelajaran $m): array => [
                $m->id => $m->nama_mapel.' — '.($m->kelas?->nama_kelas ?? 'Tanpa kelas'),
            ])
            ->all();
    }

    /** @return array<int, string> */
    protected function jamOptions(): array
    {
        return PresensiJamPelajaran::aktifUntuk(Auth::user()->pesantren_id)
            ->mapWithKeys(fn (PresensiJamPelajaran $jam): array => [
                $jam->jam_ke => $jam->labelPilihan(),
            ])
            ->all();
    }

    /**
     * Mapel yang benar-benar boleh disentuh pengguna ini, atau null.
     *
     * Dipakai membangun baris DAN menyimpan. Mengembalikan model, bukan boolean,
     * supaya kelas_id-nya ikut terbawa — kelas presensi jam pelajaran selalu
     * diturunkan dari mapel, tidak pernah dari kiriman klien.
     *
     * Publik supaya bisa diuji langsung, sepola tanggalDalamJendelaEdit(): lewat
     * UI, mapel di luar cakupan hampir selalu ditolak lebih dulu oleh validasi
     * Select (nilainya tidak ada di daftar opsi), sehingga jalur ini tidak pernah
     * tersentuh dari form — dan justru itulah alasannya ada. Yang ia jaga adalah
     * request Livewire yang dirakit tangan.
     */
    public function mapelTerpilih(int|string|null $mapelId): ?MataPelajaran
    {
        if (! $mapelId) {
            return null;
        }

        $mapel = MataPelajaran::find($mapelId);

        if (! $mapel) {
            return null;
        }

        if ($this->isUstadz() && ! PenugasanUstadz::mataPelajaranIdsDiampu()->contains($mapel->id)) {
            return null;
        }

        return $mapel;
    }

    protected function buildRows(?string $tanggal, int|string|null $mapelId, int|string|null $jamKe): array
    {
        $mapel = $this->mapelTerpilih($mapelId);

        if (! $tanggal || ! $mapel || ! $jamKe) {
            return [];
        }

        $santriList = Santri::where('status_aktif', true)
            ->where('kelas_id', $mapel->kelas_id)
            ->orderBy('nama_lengkap')
            ->get(['id', 'nama_lengkap', 'kelas_id']);

        $existing = Presensi::whereDate('tanggal', $tanggal)
            ->where('jam_ke', (int) $jamKe)
            ->where('mata_pelajaran_id', $mapel->id)
            ->whereIn('santri_id', $santriList->pluck('id'))
            ->get()
            ->keyBy('santri_id');

        return $santriList->map(function (Santri $santri) use ($existing) {
            $rec = $existing->get($santri->id);

            return [
                'santri_id' => $santri->id,
                'nama' => $santri->nama_lengkap,
                'status' => $rec?->status?->value ?? StatusKehadiran::Hadir->value,
                'catatan' => $rec?->catatan,
            ];
        })->values()->toArray();
    }

    /**
     * Kenapa halaman ini tidak bisa dipakai, kalau memang tidak bisa.
     *
     * @return array{judul: string, saran: string}|null
     */
    public function peringatanKosong(): ?array
    {
        if (! $this->pengaturan()->presensi_per_jam_aktif) {
            return [
                'judul' => 'Presensi per jam pelajaran belum diaktifkan.',
                'saran' => Auth::user()?->role === UserRole::AdminPesantren->value
                    ? 'Nyalakan lewat menu Kehadiran → Pengaturan, bagian "Presensi per Jam Pelajaran".'
                    : 'Minta admin pesantren menyalakannya lewat Pengaturan Presensi.',
            ];
        }

        if ($this->mapelOptions() === []) {
            return [
                'judul' => $this->isUstadz()
                    ? 'Anda belum ditetapkan sebagai pengampu mata pelajaran.'
                    : 'Belum ada mata pelajaran.',
                'saran' => $this->isUstadz()
                    ? 'Presensi jam pelajaran diisi oleh pengampu mapel. Minta admin pesantren menetapkan Anda lewat menu Akademik → Mata Pelajaran.'
                    : 'Tambahkan mata pelajaran lebih dulu lewat menu Akademik → Mata Pelajaran.',
            ];
        }

        return null;
    }

    /** Sama seperti presensi harian: MEMPERINGATKAN, bukan melarang. */
    public function peringatanHariLibur(): ?string
    {
        $tanggal = $this->tanggal ?? Waktu::hariIni();

        return PresensiKalender::untuk(Auth::user()->pesantren_id)
            ->keteranganLibur($tanggal);
    }

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
        $pengaturan = $this->pengaturan();
        $batasAwal = $this->isUstadz() ? $pengaturan->batasAwalEditUstadz() : null;

        return $schema
            ->components([
                DatePicker::make('tanggal')
                    ->label('Tanggal')
                    ->required()
                    ->maxDate(Waktu::akhirHariIni())
                    ->minDate($batasAwal)
                    ->helperText($batasAwal
                        ? 'Anda dapat mengisi/mengubah presensi maksimal '.$pengaturan->batas_edit_ustadz_hari.' hari ke belakang.'
                        : null)
                    ->native(false)
                    ->closeOnDateSelection()
                    ->live()
                    ->afterStateUpdated(fn (Get $get, callable $set) => $this->refreshRows($get, $set)),

                Select::make('mata_pelajaran_id')
                    ->label('Mata Pelajaran')
                    ->options(fn () => $this->mapelOptions())
                    ->required()
                    ->searchable()
                    ->helperText('Kelas mengikuti mata pelajaran yang dipilih.')
                    ->live()
                    ->afterStateUpdated(fn (Get $get, callable $set) => $this->refreshRows($get, $set)),

                Select::make('jam_ke')
                    ->label('Jam ke-')
                    ->options(fn () => $this->jamOptions())
                    ->required()
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

    protected function refreshRows(Get $get, callable $set): void
    {
        $set('rows', $this->buildRows(
            $get('tanggal'),
            $get('mata_pelajaran_id'),
            $get('jam_ke'),
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

        // Fitur mati = tidak ada yang boleh ditulis, apa pun isi request-nya.
        // peringatanKosong() hanya menjaga LAYAR; ini yang menjaga datanya.
        if (! $this->pengaturan()->presensi_per_jam_aktif) {
            Notification::make()
                ->title('Presensi per jam pelajaran tidak aktif')
                ->body('Fitur ini dimatikan untuk pesantren Anda.')
                ->danger()
                ->send();

            return;
        }

        $mapel = $this->mapelTerpilih($data['mata_pelajaran_id'] ?? null);
        $jamKe = (int) ($data['jam_ke'] ?? 0);

        if (! $mapel) {
            Notification::make()
                ->title('Mata pelajaran tidak valid')
                ->body('Pilih mata pelajaran yang Anda ampu lebih dulu.')
                ->danger()
                ->send();

            return;
        }

        // jam_ke = 0 adalah presensi HARIAN. Membiarkannya lolos di sini akan
        // membuat halaman ini menimpa presensi harian santri lewat unique
        // (santri_id, tanggal, jam_ke) — diam-diam, dan dengan sumber yang salah.
        if ($jamKe < 1) {
            Notification::make()
                ->title('Jam pelajaran tidak valid')
                ->body('Pilih jam pelajaran lebih dulu.')
                ->danger()
                ->send();

            return;
        }

        if (! $this->tanggalDalamJendelaEdit($data['tanggal'])) {
            Notification::make()
                ->title('Tanggal di luar batas edit')
                ->body('Anda hanya dapat mengisi presensi maksimal '.$this->pengaturan()->batas_edit_ustadz_hari.' hari ke belakang. Hubungi admin pesantren untuk memperbaiki data yang lebih lama.')
                ->danger()
                ->send();

            return;
        }

        // santri_id dari klien tidak dipercaya. Repeater mengirimkan kembali apa
        // pun yang ada di state-nya, dan request Livewire yang dirakit tangan bisa
        // memuat santri kelas mana saja di pesantren ini. Yang menentukan siapa
        // yang boleh ditulis adalah KELAS MILIK MAPEL, bukan kiriman.
        $santriSah = Santri::where('status_aktif', true)
            ->where('kelas_id', $mapel->kelas_id)
            ->pluck('id')
            ->all();

        $rows = array_values(array_filter(
            $data['rows'] ?? [],
            fn (array $row): bool => in_array((int) $row['santri_id'], $santriSah, true),
        ));

        if ($rows === []) {
            Notification::make()
                ->title('Tidak ada santri untuk disimpan')
                ->body('Kelas mata pelajaran ini belum punya santri aktif.')
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
            'jam_ke' => $jamKe,
            'mata_pelajaran_id' => $mapel->id,
            'kelas_id' => $mapel->kelas_id,
            'status' => $row['status'],
            'catatan' => $row['catatan'] ?? null,
            'sumber' => SumberPresensi::Manual->value,
            'dicatat_oleh' => Auth::id(),
            'dicatat_at' => $sekarang,
            'created_at' => $sekarang,
            'updated_at' => $sekarang,
        ], $rows);

        try {
            // upsert() dengan conflict target yang sama seperti presensi harian.
            // mata_pelajaran_id sengaja ikut diperbarui: mengganti mapel untuk jam
            // yang sama pada tanggal yang sama adalah koreksi yang wajar (salah
            // pilih mapel), bukan baris baru.
            DB::transaction(fn () => Presensi::upsert(
                $baris,
                ['santri_id', 'tanggal', 'jam_ke'],
                ['status', 'catatan', 'kelas_id', 'mata_pelajaran_id', 'sumber', 'dicatat_oleh', 'dicatat_at', 'updated_at'],
            ));
        } catch (\Throwable $e) {
            Log::error('presensi_jam_save_failed', ['message' => $e->getMessage()]);

            Notification::make()
                ->title('Gagal menyimpan presensi')
                ->body('Terjadi kesalahan saat menyimpan data. Silakan coba lagi, atau hubungi admin bila berulang.')
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Presensi jam ke-'.$jamKe.' tersimpan untuk '.count($rows).' santri.')
            ->success()
            ->send();
    }

    /** Publik supaya bisa diuji langsung — lihat catatan di PresensiHarianPage. */
    public function tanggalDalamJendelaEdit(?string $tanggal): bool
    {
        if (! $this->isUstadz()) {
            return true;
        }

        $batasAwal = $this->pengaturan()->batasAwalEditUstadz();

        return $batasAwal === null || $tanggal >= $batasAwal;
    }
}
