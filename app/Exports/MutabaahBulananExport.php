<?php

namespace App\Exports;

use App\Models\KesantrianMutabaah;
use App\Models\Santri;
use App\Services\MutabaahScoreCalculator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class MutabaahBulananExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    private Collection $amalMaster;

    public function __construct(
        private int $pesantrenId,
        private int $bulan,
        private int $tahun,
        private ?int $ustadzId = null,
    ) {
        $this->amalMaster = MutabaahScoreCalculator::masterAktif($pesantrenId);
    }

    public function collection(): Collection
    {
        return KesantrianMutabaah::with('santri')
            ->where('pesantren_id', $this->pesantrenId)
            ->whereMonth('tanggal', $this->bulan)
            ->whereYear('tanggal', $this->tahun)
            ->when($this->ustadzId, fn ($q) => $q->whereIn('santri_id', Santri::idsPembimbing($this->ustadzId)))
            ->orderBy('tanggal')
            ->orderBy('santri_id')
            ->get();
    }

    public function headings(): array
    {
        return array_merge(
            ['Santri', 'Tanggal', 'Status Udzur'],
            $this->amalMaster->pluck('label')->toArray(),
            ['Skor (%)'],
        );
    }

    public function map($record): array
    {
        $amalan = $record->amalan ?? [];

        $amalValues = $this->amalMaster->map(function ($amal) use ($amalan) {
            $value = $amalan[$amal->kode] ?? null;

            return $amal->tipe === 'boolean'
                ? ($value ? 'Ya' : 'Tidak')
                : ($value ?? 0);
        })->toArray();

        return array_merge(
            [
                $record->santri?->nama_lengkap ?? '-',
                $record->tanggal->format('d/m/Y'),
                str_replace('_', ' ', $record->status_udzur ?? ''),
            ],
            $amalValues,
            [MutabaahScoreCalculator::persentase($record)],
        );
    }

    public function title(): string
    {
        return 'Mutabaah '.Carbon::create($this->tahun, $this->bulan)->translatedFormat('F Y');
    }
}
