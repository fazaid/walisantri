<?php

namespace App\Models;

use App\Enums\StatusKehadiran;
use App\Enums\SumberPresensi;
use App\Models\Concerns\BelongsToPesantren;
use App\Models\Concerns\BelongsToSantri;
use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// #[Table] WAJIB — tanpa ini Laravel menebak tabelnya 'presensis'.
#[Table('presensi')]
#[Fillable([
    'pesantren_id',
    'santri_id',
    'tanggal',
    'jam_ke',
    'mata_pelajaran_id',
    'kelas_id',
    'status',
    'menit_terlambat',
    'catatan',
    'sumber',
    'presensi_izin_id',
    'dicatat_oleh',
    'dicatat_at',
])]
class Presensi extends Model
{
    use BelongsToPesantren, BelongsToSantri, Multitenantable;

    /** Nilai jam_ke untuk presensi harian (bukan jam pelajaran). */
    public const HARIAN = 0;

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jam_ke' => 'integer',
            'menit_terlambat' => 'integer',
            'status' => StatusKehadiran::class,
            'sumber' => SumberPresensi::class,
            'dicatat_at' => 'datetime',
        ];
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class);
    }

    public function izin(): BelongsTo
    {
        return $this->belongsTo(PresensiIzin::class, 'presensi_izin_id');
    }
}
