<?php

// ============================================================
// FILE 3: app/Models/KesantrianMutabaah.php
// ============================================================

namespace App\Models;

use App\Models\Concerns\BelongsToPesantren;
use App\Models\Concerns\BelongsToSantri;
use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('kesantrian_mutabaah')]
#[Fillable([
    'pesantren_id',
    'santri_id',
    'tanggal',
    'amalan',
    'status_udzur',
])]
class KesantrianMutabaah extends Model
{
    use BelongsToPesantren, BelongsToSantri, Multitenantable;

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'amalan' => 'array',
        ];
    }
}
