<?php

// File: app/Models/TahfidzProgress.php
// Replace seluruh isi file dengan kode ini.

namespace App\Models;

use App\Models\Concerns\BelongsToPesantren;
use App\Models\Concerns\BelongsToSantri;
use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('tahfidz_progress')]
#[Fillable([
    'pesantren_id',
    'santri_id',
    'ustadz_id',
    'tanggal',
    'tipe_setoran',
    'nama_surah',
    'halaman_mulai',
    'halaman_selesai',
    'nilai_kelancaran',
    'catatan_evaluasi',
])]
class TahfidzProgress extends Model
{
    use BelongsToPesantren, BelongsToSantri, Multitenantable;

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'halaman_mulai' => 'integer',
            'halaman_selesai' => 'integer',
        ];
    }

    public function getNamaSantriAttribute(): string
    {
        return $this->santri?->nama_lengkap ?? '—';
    }

    public function ustadz(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ustadz_id');
    }
}
