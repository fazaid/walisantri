<?php

// ============================================================
// FILE 5: app/Models/KesantrianInventaris.php
// ============================================================

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    use Multitenantable;

    protected function casts(): array
    {
        return [
            'tanggal_sidak_terakhir' => 'date',
        ];
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class);
    }

    public function pesantren(): BelongsTo
    {
        return $this->belongsTo(Pesantren::class);
    }
}
