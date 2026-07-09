<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class SantriTemplateExport implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    public function collection(): Collection
    {
        return collect([
            [
                '2024001',
                'Ahmad Fauzan',
                'Fauzan',
                '15/03/2010',
                'Laki-laki',
                'Hasan',
                'Fatimah',
                'Jl. Merpati No. 5, Jakarta',
                '2',
                'Dokter',
                'Kelas 7A',
                'Kamar Mawar',
                'Aktif',
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'nis',
            'nama_lengkap',
            'nama_panggilan',
            'tanggal_lahir',
            'jenis_kelamin',
            'nama_ayah',
            'nama_ibu',
            'alamat_lengkap',
            'jumlah_saudara',
            'cita_cita',
            'kelas',
            'kamar',
            'status',
        ];
    }

    public function title(): string
    {
        return 'Template Import Santri';
    }
}
