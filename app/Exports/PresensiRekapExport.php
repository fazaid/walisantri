<?php

namespace App\Exports;

use App\Enums\StatusKehadiran;
use App\Services\PresensiRekap;
use App\Services\TahunAjaranOptions;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Ekspor rekap kehadiran.
 *
 * Angkanya diambil dari PresensiRekap yang sama persis dengan halaman Rekap —
 * bukan query sendiri. Itu keputusan sadar: modul rapor pernah punya versi query
 * terpisah antara halaman dan PDF, keduanya menyimpang, dan menyimpangnya baru
 * ketahuan setahun kemudian (§15).
 */
class PresensiRekapExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    private PresensiRekap $rekap;

    public function __construct(
        int $pesantrenId,
        private readonly string $tahunAjaran,
        private readonly string $periode,
        private readonly ?string $bulan = null,
        ?int $kelasId = null,
        ?int $ustadzId = null,
    ) {
        [$awal, $akhir] = TahunAjaranOptions::rentangTanggal($tahunAjaran, $periode, $bulan);

        $this->rekap = PresensiRekap::untuk(
            $pesantrenId,
            $awal->toDateString(),
            $akhir->toDateString(),
            $kelasId,
            $ustadzId,
        );
    }

    public function collection(): Collection
    {
        return $this->rekap->perSantri();
    }

    public function headings(): array
    {
        $status = array_map(fn (StatusKehadiran $s) => $s->label(), StatusKehadiran::cases());

        return array_merge(
            ['Santri', 'Kelas'],
            $status,
            ['Tanpa Keterangan', 'Hari Efektif', '% Kehadiran'],
        );
    }

    /** @param  object  $row */
    public function map($row): array
    {
        $status = array_map(
            fn (StatusKehadiran $s) => (int) $row->{'jml_'.strtolower($s->value)},
            StatusKehadiran::cases(),
        );

        return array_merge(
            [$row->nama_lengkap, $row->nama_kelas ?? '-'],
            $status,
            [$row->tanpa_keterangan, $row->hari_efektif, $row->persen_kehadiran],
        );
    }

    public function title(): string
    {
        return 'Rekap '.TahunAjaranOptions::labelPeriode($this->periode, $this->bulan, $this->tahunAjaran);
    }
}
