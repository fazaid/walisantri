<?php

// ============================================================
// FILE 6: app/Models/MasterPengumuman.php
// ============================================================

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('master_pengumuman')]
#[Fillable([
    'pesantren_id',
    'judul_maklumat',
    'isi_maklumat',
])]
class MasterPengumuman extends Model
{
    use Multitenantable;

    public function pesantren(): BelongsTo
    {
        return $this->belongsTo(Pesantren::class);
    }
}

