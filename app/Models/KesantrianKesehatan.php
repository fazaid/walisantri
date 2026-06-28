<?php

// File: app/Models/KesantrianKesehatan.php
// Replace seluruh isi file dengan kode ini.

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('kesantrian_kesehatan')]
#[Fillable([
    'pesantren_id',
    'santri_id',
    'jenis_rekam',
    'tanggal_periksa',
    'berat_badan',
    'tinggi_badan',
    'kategori_keluhan',
    'detail_keluhan_teks',
    'tindakan_dan_obat',
    'status_pemulihan',
    'tanggal_sembuh',
])]
class KesantrianKesehatan extends Model
{
    use Multitenantable;

    protected function casts(): array
    {
        return [
            'tanggal_periksa' => 'date',
            'tanggal_sembuh'  => 'date',
            'berat_badan'     => 'float',
            'tinggi_badan'    => 'float',
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