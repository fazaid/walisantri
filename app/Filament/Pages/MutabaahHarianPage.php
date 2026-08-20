<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\Kesantrian;
use App\Filament\Support\ModulKomponen;
use App\Models\KesantrianAmalMaster;
use App\Models\KesantrianMutabaah;
use App\Models\Santri;
use App\Support\Waktu;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MutabaahHarianPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static ?string $cluster = Kesantrian::class;

    protected static ?string $navigationLabel = 'Isi Harian';

    protected static ?string $title = 'Isi Mutabaah Harian';

    protected static ?int $navigationSort = 2;

    /**
     * Bukan tab tersendiri di cluster Mutabaah — halaman ini hanyalah cara lain
     * mengisi data yang sama dengan daftar Mutabaah, jadi masuknya lewat tombol
     * di header ListKesantrianMutabaahs. canAccess() tetap menjaga akses URL
     * langsung.
     */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'isi-harian';

    protected string $view = 'filament.pages.mutabaah-harian-page';

    public ?string $tanggal = null;

    public array $rows = [];

    public const STATUS_UDZUR_OPTIONS = [
        'Tidak' => 'Tidak',
        'Sakit' => 'Sakit',
        'Haid' => 'Haid',
        'Izin_Pulang' => 'Izin Pulang',
        'Tugas_Pondok' => 'Tugas Pondok',
    ];

    protected ?Collection $amalMasterList = null;

    public static function canAccess(): bool
    {
        return in_array(Auth::user()?->role, ['admin_pesantren', 'ustadz'])
            && ModulKomponen::aktif(static::class);
    }

    public function mount(): void
    {
        // WIB, bukan UTC — input subuh (00.00–07.00 WIB) tidak boleh jatuh ke
        // tanggal kemarin.
        $tanggal = Waktu::hariIni();

        $this->form->fill([
            'tanggal' => $tanggal,
            'rows' => $this->buildRows($tanggal),
        ]);
    }

    protected function amalMasterList(): Collection
    {
        return $this->amalMasterList ??= KesantrianAmalMaster::where('pesantren_id', Auth::user()?->pesantren_id)
            ->where('aktif', true)
            ->orderBy('urutan')
            ->get();
    }

    protected function getSantriQuery(): Builder
    {
        $query = Santri::where('status_aktif', true);

        if (Auth::user()?->role === 'ustadz') {
            $query->where('pembimbing_ustadz_id', Auth::id());
        }

        return $query;
    }

    protected function buildRows(?string $tanggal): array
    {
        if (! $tanggal) {
            return [];
        }

        $santriList = $this->getSantriQuery()->orderBy('nama_lengkap')->get(['id', 'nama_lengkap']);

        $existing = KesantrianMutabaah::where('tanggal', $tanggal)
            ->whereIn('santri_id', $santriList->pluck('id'))
            ->get()
            ->keyBy('santri_id');

        $masterList = $this->amalMasterList();

        return $santriList->map(function (Santri $santri) use ($existing, $masterList) {
            $rec = $existing->get($santri->id);
            $amalan = $rec?->amalan ?? [];

            $defaultAmalan = $masterList->mapWithKeys(function (KesantrianAmalMaster $item) use ($amalan) {
                $default = $item->tipe === 'hitungan' ? $item->nilai_maks : true;

                return [$item->kode => $amalan[$item->kode] ?? $default];
            })->all();

            return [
                'santri_id' => $santri->id,
                'nama' => $santri->nama_lengkap,
                'amalan' => $defaultAmalan,
                'status_udzur' => $rec?->status_udzur ?? 'Tidak',
            ];
        })->values()->toArray();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('tanggal')
                    ->label('Tanggal')
                    ->required()
                    ->maxDate(Waktu::akhirHariIni())
                    ->native(false)
                    // Kalender Filament (native(false)) bawaannya tetap terbuka
                    // setelah tanggal diklik; di sini tidak ada bagian jam yang
                    // perlu diisi lagi, jadi ditutup begitu tanggal dipilih.
                    ->closeOnDateSelection()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $set('rows', $this->buildRows($state));
                    }),

                Repeater::make('rows')
                    ->hiddenLabel()
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->itemLabel(fn (array $state): ?string => $state['nama'] ?? null)
                    ->schema([
                        Hidden::make('santri_id'),
                        Hidden::make('nama'),

                        Select::make('status_udzur')
                            ->label('Udzur')
                            ->options(self::STATUS_UDZUR_OPTIONS)
                            ->required(),

                        ...$this->amalanFields(),
                    ])
                    ->columns(['default' => 2, 'md' => 4]),
            ]);
    }

    protected function amalanFields(): array
    {
        return $this->amalMasterList()->map(function (KesantrianAmalMaster $item) {
            $label = trim(($item->icon ? $item->icon.' ' : '').$item->label);

            if ($item->tipe === 'hitungan') {
                return TextInput::make("amalan.{$item->kode}")
                    ->label("{$label} (dari {$item->nilai_maks})")
                    ->numeric()
                    ->minValue(0)
                    ->maxValue($item->nilai_maks)
                    ->required();
            }

            return Toggle::make("amalan.{$item->kode}")
                ->label($label)
                ->inline(false);
        })->all();
    }

    /**
     * Kenapa halaman ini tidak bisa dipakai, kalau memang tidak bisa — dibaca
     * view sebelum form dirender.
     *
     * Dua penyebabnya sama-sama membuat halaman tampak berfungsi padahal tidak:
     * tanpa amal master, tiap baris santri hanya punya dropdown Udzur dan skor
     * selalu 0%; tanpa santri, Repeater kosong dan menyimpan hanya menghasilkan
     * "tersimpan untuk 0 santri". Keduanya perlu tindakan orang lain (admin),
     * jadi pesannya menyebut langkah konkret, bukan sekadar "data kosong".
     *
     * @return array{judul: string, saran: string}|null
     */
    public function peringatanKosong(): ?array
    {
        if ($this->amalMasterList()->isEmpty()) {
            return [
                'judul' => 'Belum ada amalan yang bisa diisi.',
                'saran' => Auth::user()?->role === 'admin_pesantren'
                    ? 'Tambahkan daftar amalan lebih dulu lewat menu Kesantrian → Mutabaah → Amal Master.'
                    : 'Minta admin pesantren mengisi daftar amalan lebih dulu (Kesantrian → Amal Master).',
            ];
        }

        if (! $this->getSantriQuery()->exists()) {
            return [
                'judul' => 'Belum ada santri yang bisa diisi mutaba\'ahnya.',
                'saran' => Auth::user()?->role === 'ustadz'
                    ? 'Anda belum dipercayakan membimbing santri mana pun. Minta admin pesantren menetapkan Anda sebagai pembimbing lewat menu Santri.'
                    : 'Tambahkan santri aktif lebih dulu lewat menu Santri.',
            ];
        }

        return null;
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
                            ->label('Simpan Semua')
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
                ->body('Belum ada santri aktif yang bisa diisi mutaba\'ahnya pada tanggal ini.')
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
            // Query builder tidak melewati cast 'array', jadi jsonb-nya
            // dikodekan di sini.
            'amalan' => json_encode($row['amalan'] ?? [], JSON_THROW_ON_ERROR),
            'status_udzur' => $row['status_udzur'],
            'created_at' => $sekarang,
            'updated_at' => $sekarang,
        ], $rows);

        try {
            // upsert(), bukan loop updateOrCreate() di dalam transaksi.
            //
            // updateOrCreate adalah SELECT lalu INSERT: dua ustadz yang menyimpan
            // tanggal yang sama bersamaan membuat INSERT kedua melanggar unique
            // (santri_id, tanggal). Karena seluruh loop dulu dibungkus satu
            // transaksi, satu tabrakan itu me-rollback SEMUA santri di batch dan
            // pengguna hanya melihat "Terjadi kesalahan" — padahal tidak ada yang
            // salah dengan datanya. ON CONFLICT DO UPDATE menyelesaikannya dalam
            // satu pernyataan: bebas balapan, dan tidak ada lagi yang bisa
            // di-rollback separuh jalan sehingga transaksi pembungkusnya pun
            // tidak diperlukan.
            //
            // Konsekuensi yang harus diingat: upsert melewati model event, jadi
            // auto-assign pesantren_id milik Multitenantable tidak menyala dan
            // kolomnya disetel manual di atas.
            //
            // Tetap dibungkus DB::transaction meski isinya satu pernyataan, dan
            // itu bukan sisa pola lama: di PostgreSQL, pernyataan yang gagal
            // membuat SELURUH transaksi berjalan jadi aborted (25P02) sehingga
            // query apa pun sesudahnya ikut ditolak. Pembungkus ini menjadikannya
            // savepoint saat save() dipanggil di dalam transaksi lain — termasuk
            // di test yang memakai RefreshDatabase — sehingga kegagalan cukup
            // membatalkan upsert-nya saja. Yang dulu jadi bug adalah LOOP di dalam
            // transaksi; satu pernyataan tidak punya yang bisa di-rollback separuh.
            DB::transaction(fn () => KesantrianMutabaah::upsert(
                $baris,
                ['santri_id', 'tanggal'],
                ['amalan', 'status_udzur', 'updated_at'],
            ));
        } catch (\Throwable $e) {
            Log::error('mutabaah_harian_save_failed', ['message' => $e->getMessage()]);

            Notification::make()
                ->title('Gagal menyimpan mutaba\'ah')
                ->body('Terjadi kesalahan saat menyimpan data. Silakan coba lagi, atau hubungi admin bila berulang.')
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Mutabaah tersimpan untuk '.count($rows).' santri.')
            ->success()
            ->send();
    }
}
