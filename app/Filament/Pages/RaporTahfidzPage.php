<?php

namespace App\Filament\Pages;

use App\Data\QuranSurah;
use App\Filament\Clusters\Tahfidz;
use App\Models\Santri;
use App\Models\TahfidzProgress;
use App\Models\TahfidzRapor;
use App\Services\TahunAjaranOptions;
use Barryvdh\DomPDF\Facade\Pdf;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class RaporTahfidzPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static ?string $cluster = Tahfidz::class;

    protected static ?string $navigationLabel = 'Rapor';

    protected static ?string $title = 'Rapor Tahfidz';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.rapor-tahfidz-page';

    public ?int $santriId = null;

    public string $tahunAjaran = '';

    public string $periode = 'Semester_Ganjil';

    public function mount(): void
    {
        $this->tahunAjaran = TahunAjaranOptions::current();
    }

    public function getTahunAjaranOptions(): array
    {
        return TahunAjaranOptions::options();
    }

    public static function canAccess(): bool
    {
        return in_array(Auth::user()?->role, ['admin_pesantren', 'ustadz']);
    }

    public function getSantriOptions(): array
    {
        $query = Santri::where('pesantren_id', Auth::user()?->pesantren_id)
            ->where('status_aktif', true);

        if (Auth::user()?->role === 'ustadz') {
            $query->where('pembimbing_ustadz_id', Auth::id());
        }

        return $query->orderBy('nama_lengkap')->pluck('nama_lengkap', 'id')->toArray();
    }

    public function getPeriodeOptions(): array
    {
        return [
            'Bulanan'         => 'Bulanan',
            'Semester_Ganjil' => 'Semester Ganjil',
            'Semester_Genap'  => 'Semester Genap',
        ];
    }

    public function getSantri(): ?Santri
    {
        return $this->santriId ? Santri::find($this->santriId) : null;
    }

    protected function getDateRange(): array
    {
        [$startYear, $endYear] = array_map('intval', explode('/', $this->tahunAjaran));

        return match ($this->periode) {
            'Semester_Ganjil' => [Carbon::create($startYear, 7, 1)->startOfDay(), Carbon::create($startYear, 12, 31)->endOfDay()],
            'Semester_Genap'  => [Carbon::create($endYear, 1, 1)->startOfDay(), Carbon::create($endYear, 6, 30)->endOfDay()],
            default           => [Carbon::create($startYear, 7, 1)->startOfDay(), Carbon::create($endYear, 6, 30)->endOfDay()],
        };
    }

    public function getUjianList(): Collection
    {
        if (! $this->santriId) {
            return collect();
        }

        return TahfidzRapor::with('penguji')
            ->where('santri_id', $this->santriId)
            ->where('tahun_ajaran', $this->tahunAjaran)
            ->where('periode', $this->periode)
            ->orderByDesc('tanggal_ujian')
            ->get();
    }

    public function getSetoranList(): Collection
    {
        if (! $this->santriId) {
            return collect();
        }

        [$start, $end] = $this->getDateRange();

        return TahfidzProgress::where('santri_id', $this->santriId)
            ->whereBetween('tanggal', [$start, $end])
            ->orderBy('tanggal')
            ->get();
    }

    public function getSetoranStats(): array
    {
        $list = $this->getSetoranList();

        if ($list->isEmpty()) {
            return [
                'total_setoran'    => 0,
                'total_ayat'       => 0,
                'hari_aktif'       => 0,
                'per_tipe'         => collect(),
                'nilai_distribusi' => collect(),
                'surah_list'       => collect(),
            ];
        }

        $jumlahAyat = fn ($p) => $p->ayat_selesai - $p->ayat_mulai + 1;

        return [
            'total_setoran'    => $list->count(),
            'total_ayat'       => $list->sum($jumlahAyat),
            'hari_aktif'       => $list->pluck('tanggal')->map(fn ($d) => $d->toDateString())->unique()->count(),
            'per_tipe'         => $list->groupBy('tipe_setoran')->map(fn ($g) => [
                'jumlah' => $g->count(),
                'ayat'   => $g->sum($jumlahAyat),
            ]),
            'nilai_distribusi' => $list->groupBy('nilai_kelancaran')->map->count(),
            'surah_list'       => $list->pluck('nama_surah')->unique()
                ->sortBy(fn ($surah) => QuranSurah::surahNoByName($surah) ?? 999)
                ->values(),
        ];
    }

    public function getTotalJuzLulus(): int
    {
        if (! $this->santriId) {
            return 0;
        }

        return (int) (TahfidzRapor::where('santri_id', $this->santriId)
            ->where('status_kelulusan', 'Lulus')
            ->max('target_juz') ?? 0);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('unduhPdf')
                ->label('Unduh PDF')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('primary')
                ->action(function () {
                    $santri = $this->getSantri();
                    $ujianList = $this->getUjianList();
                    $setoranList = $this->getSetoranList();

                    if (! $santri || ($ujianList->isEmpty() && $setoranList->isEmpty())) {
                        Notification::make()
                            ->title('Belum ada data tahfidz untuk pilihan ini')
                            ->warning()
                            ->send();

                        return;
                    }

                    $pdf = Pdf::loadView('filament.pdf.rapor-tahfidz', [
                        'santri'         => $santri,
                        'ujianList'      => $ujianList,
                        'setoranStats'   => $this->getSetoranStats(),
                        'totalJuzLulus'  => $this->getTotalJuzLulus(),
                        'tahunAjaran'    => $this->tahunAjaran,
                        'periode'        => $this->periode,
                    ])->setPaper('A4', 'portrait');

                    $filename = 'Rapor-Tahfidz-'
                        . str_replace(' ', '-', $santri->nama_lengkap)
                        . '-' . str_replace('/', '-', $this->tahunAjaran)
                        . '.pdf';

                    return response()->streamDownload(
                        fn () => print ($pdf->output()),
                        $filename,
                        ['Content-Type' => 'application/pdf'],
                    );
                }),
        ];
    }
}
