<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('master_pengumuman_central')]
#[Fillable(['judul_maklumat', 'isi_maklumat', 'is_active'])]
class MasterPengumumanCentral extends Model
{
    // Tidak pakai Multitenantable — pengumuman ini global, lintas tenant.

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
