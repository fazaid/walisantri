<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\Rapor;
use App\Models\KesantrianAmalMaster;
use App\Models\KesantrianMutabaah;
use App\Models\Santri;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class RaporMutabaahPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $cluster = Rapor::class;

    protected static ?string $navigationLabel = 'Mutabaah';

    protected static ?string $title = 'Rapor Mutabaah';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.rapor-mutabaah-page';

    public ?int $santriId = null;
    public int $bulan;
    public string $tahun;

    public function mount(): void
    {
        $this->bulan = (int) now()->month;
        $this->tahun = (string) now()->year;
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

    public function getBulanOptions(): array
    {
        return [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
    }

    public function getTahunOptions(): array
    {
        $tahun = (int) now()->year;
        $options = [];
        for ($y = $tahun; $y >= $tahun - 3; $y--) {
            $options[(string) $y] = (string) $y;
        }
        return $options;
    }

    public function getSantri(): ?Santri
    {
        return $this->santriId ? Santri::with('pesantren', 'kelas')->find($this->santriId) : null;
    }

    public function getMutabaahRecords(): Collection
    {
        if (! $this->santriId) return collect();

        return KesantrianMutabaah::where('santri_id', $this->santriId)
            ->whereYear('tanggal', $this->tahun)
            ->whereMonth('tanggal', $this->bulan)
            ->orderBy('tanggal')
            ->get();
    }

    public function getAmalMasters(): Collection
    {
        return KesantrianAmalMaster::where('pesantren_id', Auth::user()?->pesantren_id)
            ->where('aktif', true)
            ->orderBy('urutan')
            ->get();
    }

    public function getRingkasan(): array
    {
        $records = $this->getMutabaahRecords();
        $masters = $this->getAmalMasters();

        if ($records->isEmpty()) return [];

        $totalHari = $records->count();
        $totalUdzur = $records->where('status_udzur', '!=', 'Tidak')->count();

        $udzurDetail = $records->where('status_udzur', '!=', 'Tidak')
            ->groupBy('status_udzur')
            ->map->count()
            ->toArray();

        $amalan = [];
        foreach ($masters as $master) {
            $kode = $master->kode;
            if ($master->tipe === 'hitungan') {
                $total = (int) $records->sum(fn ($r) => $r->amalan[$kode] ?? 0);
                $maks  = $totalHari * $master->nilai_maks;
                $persen = $maks > 0 ? (int) round($total / $maks * 100) : 0;
                $amalan[] = [
                    'label'       => ($master->icon ? $master->icon . ' ' : '') . $master->label,
                    'tipe'        => 'hitungan',
                    'nilai_maks'  => $master->nilai_maks,
                    'total_capai' => $total,
                    'total_maks'  => $maks,
                    'persen'      => $persen,
                ];
            } else {
                $total  = $records->filter(fn ($r) => !empty($r->amalan[$kode]))->count();
                $persen = $totalHari > 0 ? (int) round($total / $totalHari * 100) : 0;
                $amalan[] = [
                    'label'       => ($master->icon ? $master->icon . ' ' : '') . $master->label,
                    'tipe'        => 'boolean',
                    'total_capai' => $total,
                    'total_maks'  => $totalHari,
                    'persen'      => $persen,
                ];
            }
        }

        $rataRata = count($amalan) > 0
            ? (int) round(array_sum(array_column($amalan, 'persen')) / count($amalan))
            : 0;

        return [
            'total_hari'   => $totalHari,
            'total_udzur'  => $totalUdzur,
            'udzur_detail' => $udzurDetail,
            'amalan'       => $amalan,
            'rata_rata'    => $rataRata,
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
                    $ringkasan = $this->getRingkasan();

                    if (! $santri || empty($ringkasan)) {
                        Notification::make()
                            ->title('Belum ada data mutabaah untuk periode ini')
                            ->warning()
                            ->send();
                        return;
                    }

                    $bulanNama = $this->getBulanOptions()[$this->bulan] ?? $this->bulan;

                    $pdf = Pdf::loadView('filament.pdf.rapor-mutabaah', [
                        'santri'    => $santri,
                        'ringkasan' => $ringkasan,
                        'bulan'     => $bulanNama,
                        'tahun'     => $this->tahun,
                    ])->setPaper('A4', 'portrait');

                    $filename = 'Rapor-Mutabaah-'
                        . str_replace(' ', '-', $santri->nama_lengkap)
                        . '-' . $bulanNama . '-' . $this->tahun . '.pdf';

                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        $filename,
                        ['Content-Type' => 'application/pdf'],
                    );
                }),
        ];
    }
}
