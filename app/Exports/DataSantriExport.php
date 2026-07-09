<?php

namespace App\Exports;

use App\Models\Santri;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class DataSantriExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private int $pesantrenId) {}

    public function query(): Builder
    {
        return Santri::with(['kelas', 'kamar', 'wali', 'pembimbing'])
            ->where('pesantren_id', $this->pesantrenId)
            ->orderBy('nama_lengkap');
    }

    public function headings(): array
    {
        return [
            'NIS',
            'Nama Lengkap',
            'Nama Panggilan',
            'Tanggal Lahir',
            'Jenis Kelamin',
            'Nama Ayah',
            'Nama Ibu',
            'Alamat Lengkap',
            'Jumlah Saudara',
            'Cita-Cita',
            'Kelas',
            'Kamar',
            'Ustadz Pembimbing',
            'Wali Nama',
            'Wali Email',
            'Wali No Hp',
            'Status',
        ];
    }

    public function map($santri): array
    {
        return [
            $santri->nis,
            $santri->nama_lengkap,
            $santri->nama_panggilan,
            $santri->tanggal_lahir?->format('d/m/Y'),
            $santri->jenis_kelamin?->label(),
            $santri->nama_ayah,
            $santri->nama_ibu,
            $santri->alamat_lengkap,
            $santri->jumlah_saudara,
            $santri->cita_cita,
            $santri->kelas?->nama_kelas,
            $santri->kamar?->nama_kamar,
            $santri->pembimbing?->name,
            $santri->wali?->name,
            $santri->wali?->email,
            $santri->wali?->phone_number,
            $santri->status_aktif ? 'Aktif' : 'Non-Aktif',
        ];
    }

    public function title(): string
    {
        return 'Data Santri';
    }
}
