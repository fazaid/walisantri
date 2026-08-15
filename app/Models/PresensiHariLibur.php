<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPesantren;
use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

// #[Table] WAJIB — tanpa ini Laravel menebak tabelnya 'presensi_hari_liburs'.
#[Table('presensi_hari_libur')]
#[Fillable([
    'pesantren_id',
    'tanggal',
    'keterangan',
    'tahun_ajaran',
])]
class PresensiHariLibur extends Model
{
    use BelongsToPesantren, Multitenantable;

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }
}
