<?php

// ============================================================
// FILE 4: app/Models/KesantrianKarakterRapor.php
// ============================================================

namespace App\Models;

use App\Models\Concerns\BelongsToPesantren;
use App\Models\Concerns\BelongsToSantri;
use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('kesantrian_karakter_rapor')]
#[Fillable([
    'pesantren_id',
    'santri_id',
    'periode',
    'tahun_ajaran',
    'bulan',
    'tanggal_input',
    'adab_ustadz',
    'adab_tamu',
    'adab_asrama',
    'adab_kelas',
    'adab_sholat',
    'adab_quran',
    'adab_minum',
    'kepribadian_tanggungjawab',
    'kepribadian_kemandirian',
    'kepribadian_kepatuhan',
    'kepribadian_kebersihan',
    'kepribadian_mengelola',
    'kepribadian_kepedulian',
    'kepribadian_empati',
    'kepribadian_kebersamaan',
    'kepribadian_kedisiplinan',
    'log_kasus_khusus',
])]
class KesantrianKarakterRapor extends Model
{
    use BelongsToPesantren, BelongsToSantri, Multitenantable;

    protected function casts(): array
    {
        return [
            'tanggal_input' => 'date',
        ];
    }
}
