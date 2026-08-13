<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Filament\Clusters\Akademik;
use App\Models\MataPelajaran;
use App\Models\NilaiAkademik;
use App\Models\Santri;
use App\Services\TahunAjaranOptions;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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

class NilaiMassalPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static ?string $cluster = Akademik::class;

    protected static ?string $navigationLabel = 'Input Nilai Massal';

    protected static ?string $title = 'Input Nilai Massal';

    /**
     * Bukan entri menu tersendiri — halaman ini hanyalah cara lain mengisi data
     * yang sama dengan menu "Nilai", jadi masuknya lewat tombol di header
     * daftar Nilai (ListNilaiAkademik). canAccess() tetap menjaga akses URL
     * langsung.
     */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'input-nilai-massal';

    protected string $view = 'filament.pages.nilai-massal-page';

    public ?string $mata_pelajaran_id = null;

    public ?string $tahun_ajaran = null;

    public ?string $periode = null;

    public ?string $bulan = null;

    public array $rows = [];

    public static function canAccess(): bool
    {
        return in_array(Auth::user()?->role, [
            UserRole::AdminPesantren->value,
            UserRole::Ustadz->value,
        ]);
    }

    public function mount(): void
    {
        $this->form->fill([
            'tahun_ajaran' => TahunAjaranOptions::current(),
            'periode' => TahunAjaranOptions::currentPeriode(),
            'rows' => [],
        ]);
    }

    /**
     * Mapel yang boleh diisi: ustadz hanya mapel yang ia ampu. Global scope
     * Multitenantable sudah menyaring pesantren_id.
     */
    protected function mapelOptions(): array
    {
        $query = MataPelajaran::with('kelas');

        if (Auth::user()?->role === UserRole::Ustadz->value) {
            $query->where('ustadz_id', Auth::id());
        }

        return $query->get()
            ->mapWithKeys(fn (MataPelajaran $mapel) => [
                $mapel->id => $mapel->nama_mapel.' — '.$mapel->kelas?->nama_kelas,
            ])
            ->all();
    }

    /**
     * Satu baris per santri di kelas mapel tersebut, di-prefill dengan nilai
     * yang sudah ada. Nilai yang sudah ada diambil dalam satu query, bukan
     * satu query per santri.
     */
    protected function buildRows(?string $mapelId, ?string $tahunAjaran, ?string $periode, ?string $bulan): array
    {
        if (! $mapelId || ! $tahunAjaran || ! $periode) {
            return [];
        }

        // Bulanan tanpa bulan belum bisa menentukan baris mana yang di-prefill.
        if ($periode === 'Bulanan' && ! $bulan) {
            return [];
        }

        $mapel = MataPelajaran::find($mapelId);

        if (! $mapel) {
            return [];
        }

        $santriList = Santri::where('kelas_id', $mapel->kelas_id)
            ->where('status_aktif', true)
            ->orderBy('nama_lengkap')
            ->get(['id', 'nama_lengkap']);

        $existing = NilaiAkademik::where('mata_pelajaran_id', $mapelId)
            ->where('tahun_ajaran', $tahunAjaran)
            ->where('periode', $periode)
            ->where('bulan', $periode === 'Bulanan' ? $bulan : null)
            ->whereIn('santri_id', $santriList->pluck('id'))
            ->get()
            ->keyBy('santri_id');

        return $santriList->map(function (Santri $santri) use ($existing) {
            $rec = $existing->get($santri->id);

            return [
                'santri_id' => $santri->id,
                'nama' => $santri->nama_lengkap,
                'nilai' => $rec?->nilai,
                'catatan' => $rec?->catatan,
            ];
        })->values()->toArray();
    }

    /**
     * Baca kombinasi terkini dari state form (bukan dari properti komponen —
     * saat afterStateUpdated berjalan, properti belum tentu sudah tersinkron).
     */
    protected function refreshRows(Get $get, callable $set): void
    {
        $set('rows', $this->buildRows(
            $get('mata_pelajaran_id'),
            $get('tahun_ajaran'),
            $get('periode'),
            $get('bulan'),
        ));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('mata_pelajaran_id')
                    ->label('Mata Pelajaran')
                    ->options(fn () => $this->mapelOptions())
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Get $get, callable $set) => $this->refreshRows($get, $set)),

                Select::make('tahun_ajaran')
                    ->label('Tahun Ajaran')
                    ->options(TahunAjaranOptions::options())
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Get $get, callable $set) {
                        $set('bulan', null);
                        $this->refreshRows($get, $set);
                    }),

                Select::make('periode')
                    ->label('Periode')
                    ->options(TahunAjaranOptions::periodeOptions())
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Get $get, callable $set) {
                        $set('bulan', null);
                        $this->refreshRows($get, $set);
                    }),

                Select::make('bulan')
                    ->label('Bulan')
                    ->options(fn (Get $get) => TahunAjaranOptions::bulanOptions($get('tahun_ajaran')))
                    ->visible(fn (Get $get) => $get('periode') === 'Bulanan')
                    ->required(fn (Get $get) => $get('periode') === 'Bulanan')
                    ->live()
                    ->afterStateUpdated(fn (Get $get, callable $set) => $this->refreshRows($get, $set)),

                Repeater::make('rows')
                    ->label('Daftar Santri')
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->itemLabel(fn (array $state): ?string => $state['nama'] ?? null)
                    ->schema([
                        Hidden::make('santri_id'),
                        Hidden::make('nama'),

                        TextInput::make('nilai')
                            ->label('Nilai (0-100)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->helperText('Kosongkan untuk melewati santri ini.'),

                        Textarea::make('catatan')
                            ->label('Catatan')
                            ->rows(2),
                    ])
                    ->columns(['default' => 1, 'md' => 2]),
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

        // bulan hanya bermakna untuk periode Bulanan; untuk semester ia NULL,
        // sesuai unique index (santri_id, mata_pelajaran_id, tahun_ajaran,
        // periode, bulan). Saat bulan NULL, unique index tidak menegakkan
        // apa pun, jadi updateOrCreate-lah yang mencegah duplikat.
        $bulan = $data['periode'] === 'Bulanan' ? ($data['bulan'] ?? null) : null;

        $tersimpan = 0;

        try {
            DB::transaction(function () use ($rows, $data, $bulan, &$tersimpan) {
                foreach ($rows as $row) {
                    if (! isset($row['nilai']) || $row['nilai'] === '' || $row['nilai'] === null) {
                        continue;
                    }

                    NilaiAkademik::updateOrCreate(
                        [
                            'santri_id' => $row['santri_id'],
                            'mata_pelajaran_id' => $data['mata_pelajaran_id'],
                            'tahun_ajaran' => $data['tahun_ajaran'],
                            'periode' => $data['periode'],
                            'bulan' => $bulan,
                        ],
                        [
                            'nilai' => (int) $row['nilai'],
                            'catatan' => $row['catatan'] ?? null,
                        ]
                    );

                    $tersimpan++;
                }
            });
        } catch (\Throwable $e) {
            Log::error('nilai_massal_save_failed', ['message' => $e->getMessage()]);

            Notification::make()
                ->title('Gagal menyimpan nilai')
                ->body('Terjadi kesalahan saat menyimpan data. Silakan coba lagi, atau hubungi admin bila berulang.')
                ->danger()
                ->send();

            return;
        }

        if ($tersimpan === 0) {
            Notification::make()
                ->title('Tidak ada nilai yang diisi.')
                ->warning()
                ->send();

            return;
        }

        Notification::make()
            ->title("Nilai tersimpan untuk {$tersimpan} santri.")
            ->success()
            ->send();

        // Muat ulang agar baris yang baru tersimpan tampil sebagai prefill.
        $this->rows = $this->buildRows(
            $data['mata_pelajaran_id'],
            $data['tahun_ajaran'],
            $data['periode'],
            $bulan,
        );
    }
}
