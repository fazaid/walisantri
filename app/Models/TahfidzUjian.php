<?php

// ============================================================
// FILE 1: app/Models/TahfidzUjian.php
// ============================================================

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('tahfidz_ujian')]
#[Fillable([
    'pesantren_id',
    'santri_id',
    'penguji_id',
    'tanggal_ujian',
    'target_juz',
    'status_kelulusan',
    'catatan_ujian',
])]
class TahfidzUjian extends Model
{
    use Multitenantable;

    protected function casts(): array
    {
        return [
            'tanggal_ujian' => 'date',
        ];
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class);
    }

    public function penguji(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penguji_id');
    }

    public function pesantren(): BelongsTo
    {
        return $this->belongsTo(Pesantren::class);
    }
}