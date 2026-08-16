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
            // Format ditulis eksplisit: cast 'date' polos menyerialkan nilainya
            // sebagai 'Y-m-d H:i:s'. Postgres memotong jam itu karena kolomnya DATE,
            // SQLite (dipakai phpunit.xml lokal) menyimpannya apa adanya — sehingga
            // pencocokan tanggal meleset dan modul ini merah secara lokal padahal
            // hijau di CI. Lihat komentar di phpunit.pgsql.xml.
            'tanggal' => 'date:Y-m-d',
        ];
    }
}
