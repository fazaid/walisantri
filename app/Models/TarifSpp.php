<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TarifSpp extends Model
{
    use Multitenantable;

    protected $table = 'tarif_spp';

    protected $fillable = [
        'pesantren_id',
        'kelas_id',
        'nominal',
        'keterangan',
    ];

    protected $casts = [
        'nominal' => 'integer',
    ];

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function pesantren(): BelongsTo
    {
        return $this->belongsTo(Pesantren::class);
    }
}
