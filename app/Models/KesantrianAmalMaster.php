<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPesantren;
use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('kesantrian_amal_master')]
#[Fillable([
    'pesantren_id',
    'kode',
    'label',
    'tipe',
    'nilai_maks',
    'satuan',
    'icon',
    'bobot',
    'urutan',
    'aktif',
])]
class KesantrianAmalMaster extends Model
{
    use BelongsToPesantren, Multitenantable;

    protected function casts(): array
    {
        return [
            'nilai_maks' => 'integer',
            'bobot' => 'integer',
            'urutan' => 'integer',
            'aktif' => 'boolean',
        ];
    }
}
