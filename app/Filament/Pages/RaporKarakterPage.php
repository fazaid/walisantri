<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\Rapor;
use App\Models\KesantrianKarakterRapor;
use App\Models\Santri;
use App\Services\TahunAjaranOptions;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class RaporKarakterPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static ?string $cluster = Rapor::class;

    protected static ?string $navigationLabel = 'Karakter';

    protected static ?string $title = 'Rapor Karakter';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.rapor-karakter-page';

    public ?int $santriId = null;
    public string $tahunAjaran = '';
    public string $periode = '';
    public string $bulan = '';

    public function mount(): void
    {
        $this->tahunAjaran = TahunAjaranOptions::current();
        $this->periode = TahunAjaranOptions::currentPeriode();
        $this->bulan = now()->month . '-' . now()->year;
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

    public function getTahunAjaranOptions(): array
    {
        return TahunAjaranOptions::options();
    }

    public function getPeriodeOptions(): array
    {
        return [
            'Bulanan'         => 'Bulanan',
            'Semester_Ganjil' => 'Semester Ganjil',
            'Semester_Genap'  => 'Semester Genap',
        ];
    }

    public function getBulanOptions(): array
    {
        [$startYear, $endYear] = array_map('intval', explode('/', $this->tahunAjaran));

        $nama = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
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

    public function getSantri(): ?Santri
    {
        return $this->santriId ? Santri::with('pesantren', 'kelas')->find($this->santriId) : null;
    }

    public function getRapor(): ?KesantrianKarakterRapor
    {
        if (! $this->santriId) return null;

        $query = KesantrianKarakterRapor::where('santri_id', $this->santriId)
            ->where('tahun_ajaran', $this->tahunAjaran)
            ->where('periode', $this->periode);

        if ($this->periode === 'Bulanan') {
            $query->where('bulan', $this->bulan);
        } else {
            $query->whereNull('bulan');
        }

        return $query->latest('tanggal_input')->first();
    }

    public function getAdabFields(): array
    {
        return [
            'adab_ustadz'  => 'Adab ke Ustadz',
            'adab_tamu'    => 'Adab ke Tamu',
            'adab_asrama'  => 'Adab Asrama',
            'adab_kelas'   => 'Adab Kelas',
            'adab_sholat'  => 'Adab Sholat',
            'adab_quran'   => 'Adab Al-Quran',
            'adab_minum'   => 'Adab Minum',
        ];
    }

    public function getKepribadianFields(): array
    {
        return [
            'kepribadian_tanggungjawab' => 'Tanggung Jawab',
            'kepribadian_kemandirian'   => 'Kemandirian',
            'kepribadian_kepatuhan'     => 'Kepatuhan',
            'kepribadian_kebersihan'    => 'Kebersihan',
            'kepribadian_mengelola'     => 'Mengelola Diri',
            'kepribadian_kepedulian'    => 'Kepedulian',
            'kepribadian_empati'        => 'Empati',
            'kepribadian_kebersamaan'   => 'Kebersamaan',
            'kepribadian_kedisiplinan'  => 'Kedisiplinan',
        ];
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
                    $rapor  = $this->getRapor();

                    if (! $santri || ! $rapor) {
                        Notification::make()
                            ->title('Belum ada rapor karakter untuk pilihan ini')
                            ->warning()
                            ->send();
                        return;
                    }

                    $periodeLabel = $this->getPeriodeOptions()[$this->periode] ?? $this->periode;
                    $bulanLabel   = $this->periode === 'Bulanan'
                        ? ($this->getBulanOptions()[$this->bulan] ?? $this->bulan)
                        : null;

                    $pdf = Pdf::loadView('filament.pdf.rapor-karakter', [
                        'santri'       => $santri,
                        'rapor'        => $rapor,
                        'tahunAjaran'  => $this->tahunAjaran,
                        'periodeLabel' => $periodeLabel,
                        'bulanLabel'   => $bulanLabel,
                        'adabFields'   => $this->getAdabFields(),
                        'kepFields'    => $this->getKepribadianFields(),
                    ])->setPaper('A4', 'portrait');

                    $filename = 'Rapor-Karakter-'
                        . str_replace(' ', '-', $santri->nama_lengkap)
                        . '-' . str_replace('/', '-', $this->tahunAjaran)
                        . '-' . $periodeLabel . '.pdf';

                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        $filename,
                        ['Content-Type' => 'application/pdf'],
                    );
                }),
        ];
    }
}
