<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    use Multitenantable;

    protected function casts(): array
    {
        return [
            'nilai_maks' => 'integer',
            'bobot'      => 'integer',
            'urutan'     => 'integer',
            'aktif'      => 'boolean',
        ];
    }

    public function pesantren(): BelongsTo
    {
        return $this->belongsTo(Pesantren::class);
    }
}
