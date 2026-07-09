<?php

// ============================================================
// FILE 5: app/Models/KesantrianInventaris.php
// ============================================================

namespace App\Models;

use App\Models\Concerns\BelongsToPesantren;
use App\Models\Concerns\BelongsToSantri;
use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('kesantrian_inventaris')]
#[Fillable([
    'pesantren_id',
    'santri_id',
    'nama_barang_umum',
    'kode_unik_fisik',
    'kuota_regulasi_maksimal',
    'kondisi_barang',
    'tanggal_sidak_terakhir',
])]
class KesantrianInventaris extends Model
{
    use BelongsToPesantren, BelongsToSantri, Multitenantable;

    protected function casts(): array
    {
        return [
            'tanggal_sidak_terakhir' => 'date',
        ];
    }
}
