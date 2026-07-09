<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPesantren;
use App\Models\Concerns\BelongsToSantri;
use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('tahfidz_rapor')]
#[Fillable([
    'pesantren_id',
    'santri_id',
    'penguji_id',
    'tanggal_ujian',
    'target_juz',
    'status_kelulusan',
    'tahun_ajaran',
    'periode',
    'bulan',
    'nilai_hafalan',
    'nilai_tilawah',
    'nilai_makhraj',
    'nilai_tajwid',
    'rekomendasi_pembimbing',
])]
class TahfidzUjian extends Model
{
    use BelongsToPesantren, BelongsToSantri, Multitenantable;

    protected function casts(): array
    {
        return [
            'tanggal_ujian' => 'date',
        ];
    }

    public function getNamaSantriAttribute(): string
    {
        return $this->santri?->nama_lengkap ?? '—';
    }

    public function penguji(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penguji_id');
    }
}
