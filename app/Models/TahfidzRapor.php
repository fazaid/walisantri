<?php

// ============================================================
// FILE 2: app/Models/TahfidzRapor.php
// ============================================================

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('tahfidz_rapor')]
#[Fillable([
    'pesantren_id',
    'santri_id',
    'tahun_ajaran',
    'periode',
    'nilai_hafalan',
    'nilai_tilawah',
    'nilai_makhraj',
    'nilai_tajwid',
    'rekomendasi_pembimbing',
])]
class TahfidzRapor extends Model
{
    use Multitenantable;

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class);
    }

    public function pesantren(): BelongsTo
    {
        return $this->belongsTo(Pesantren::class);
    }
}
