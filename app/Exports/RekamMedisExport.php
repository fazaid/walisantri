<?php

namespace App\Exports;

use App\Models\KesantrianKesehatan;
use App\Models\Santri;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class RekamMedisExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    public function __construct(
        private int $pesantrenId,
        private ?string $dari = null,
        private ?string $sampai = null,
        private ?int $ustadzId = null,
    ) {}

    public function query(): Builder
    {
        return KesantrianKesehatan::with('santri')
            ->where('pesantren_id', $this->pesantrenId)
            ->when($this->dari, fn ($q) => $q->whereDate('tanggal_periksa', '>=', $this->dari))
            ->when($this->sampai, fn ($q) => $q->whereDate('tanggal_periksa', '<=', $this->sampai))
            ->when($this->ustadzId, fn ($q) => $q->whereIn('santri_id', Santri::idsPembimbing($this->ustadzId)))
            ->orderBy('tanggal_periksa', 'desc')
            ->orderBy('santri_id');
    }

    public function headings(): array
    {
        return [
            'Santri',
            'Tanggal Periksa',
            'Jenis Rekam',
            'BB (kg)',
            'TB (cm)',
            'Kategori Keluhan',
            'Detail Keluhan',
            'Tindakan & Obat',
            'Status Pemulihan',
            'Tanggal Sembuh',
        ];
    }

    public function map($record): array
    {
        return [
            $record->santri?->nama_lengkap ?? '-',
            $record->tanggal_periksa->format('d/m/Y'),
            $record->jenis_rekam === 'rutin' ? 'Pemeriksaan Rutin' : 'Keluhan Sakit',
            $record->berat_badan,
            $record->tinggi_badan,
            str_replace('_', ' ', $record->kategori_keluhan ?? '-'),
            $record->detail_keluhan_teks ?? '-',
            $record->tindakan_dan_obat ?? '-',
            str_replace('_', ' ', $record->status_pemulihan ?? '-'),
            $record->tanggal_sembuh?->format('d/m/Y') ?? '-',
        ];
    }

    public function title(): string
    {
        return 'Rekam Medis';
    }
}
