<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPesantren;
use App\Models\Concerns\BelongsToSantri;
use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('nilai_akademik')]
#[Fillable([
    'pesantren_id',
    'santri_id',
    'mata_pelajaran_id',
    'tahun_ajaran',
    'periode',
    'bulan',
    'nilai',
    'catatan',
])]
class NilaiAkademik extends Model
{
    use BelongsToPesantren, BelongsToSantri, HasFactory, Multitenantable;

    protected function casts(): array
    {
        return [
            'nilai' => 'integer',
        ];
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class);
    }
}
