<?php

// ============================================================
// FILE 3: app/Models/KesantrianMutabaah.php
// ============================================================

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('kesantrian_mutabaah')]
#[Fillable([
    'pesantren_id',
    'santri_id',
    'tanggal',
    'jamaah_5_waktu',
    'is_rawatib',
    'is_shalat_malam',
    'is_dhuha',
    'is_tilawah_1juz',
    'is_infak',
    'is_puasa',
    'status_udzur',
])]
class KesantrianMutabaah extends Model
{
    use Multitenantable;

    protected function casts(): array
    {
        return [
            'tanggal'         => 'date',
            'is_rawatib'      => 'boolean',
            'is_shalat_malam' => 'boolean',
            'is_dhuha'        => 'boolean',
            'is_tilawah_1juz' => 'boolean',
            'is_infak'        => 'boolean',
            'is_puasa'        => 'boolean',
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
