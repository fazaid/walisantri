<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\Rapor;
use App\Models\MataPelajaran;
use App\Models\NilaiAkademik;
use App\Models\SantriEkskul;
use App\Models\Santri;
use App\Services\TahunAjaranOptions;
use Barryvdh\DomPDF\Facade\Pdf;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class RaporAkademikPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static ?string $cluster = Rapor::class;

    protected static ?string $navigationLabel = 'Akademik';

    protected static ?string $title = 'Rapor Akademik';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.rapor-akademik-page';

    public ?int $santriId = null;

    public string $tahunAjaran = '';

    public string $periode = '';

    public string $bulan = '';

    public function mount(): void
    {
        $this->tahunAjaran = TahunAjaranOptions::current();
        $this->periode     = TahunAjaranOptions::currentPeriode();
        $this->bulan       = now()->month . '-' . now()->year;
    }

    public function getBulanOptions(): array
    {
        [$startYear, $endYear] = array_map('intval', explode('/', $this->tahunAjaran));

        $nama = [
            1=>'Januari', 2=>'Februari', 3=>'Maret',    4=>'April',
            5=>'Mei',     6=>'Juni',     7=>'Juli',      8=>'Agustus',
            9=>'September',10=>'Oktober',11=>'November',12=>'Desember',
        ];

        $options = [];
        for ($m = 7; $m <= 12; $m++) {
            $options["{$m}-{$startYear}"] = $nama[$m] . ' ' . $startYear;
        }
        for ($m = 1; $m <= 6; $m++) {
            $options["{$m}-{$endYear}"] = $nama[$m] . ' ' . $endYear;
        }

        return $options;
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
            $kelasIds = MataPelajaran::where('ustadz_id', Auth::id())->pluck('kelas_id');
            $query->whereIn('kelas_id', $kelasIds);
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

    public function getNilaiList(): Collection
    {
        if (! $this->santriId) {
            return collect();
        }

        return NilaiAkademik::with('mataPelajaran')
            ->where('santri_id', $this->santriId)
            ->where('tahun_ajaran', $this->tahunAjaran)
            ->where('periode', $this->periode)
            ->when($this->periode === 'Bulanan' && $this->bulan, fn ($q) => $q->where('bulan', $this->bulan))
            ->get();
    }

    public function getEkskulList(): Collection
    {
        if (! $this->santriId) {
            return collect();
        }

        return SantriEkskul::with('ekskulMaster')
            ->where('santri_id', $this->santriId)
            ->where('aktif', true)
            ->orderBy('tanggal_mulai')
            ->get();
    }

    public function getRataRata(): ?float
    {
        $nilai = $this->getNilaiList();

        return $nilai->isNotEmpty() ? round((float) $nilai->avg('nilai'), 1) : null;
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
                    $nilai = $this->getNilaiList();

                    if (! $santri || $nilai->isEmpty()) {
                        Notification::make()
                            ->title('Belum ada data nilai untuk pilihan ini')
                            ->warning()
                            ->send();

                        return;
                    }

                    $pdf = Pdf::loadView('filament.pdf.rapor-akademik', [
                        'santri'      => $santri,
                        'nilai'       => $nilai,
                        'rataRata'    => $this->getRataRata(),
                        'tahunAjaran' => $this->tahunAjaran,
                        'periode'     => $this->periode,
                    ])->setPaper('A4', 'portrait');

                    $filename = 'Rapor-Akademik-'
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
