<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('santri_ekskuls')]
#[Fillable(['pesantren_id', 'santri_id', 'ekskul_id', 'level', 'tanggal_mulai', 'aktif'])]
class SantriEkskul extends Model
{
    use HasFactory, Multitenantable;

    protected $casts = [
        'tanggal_mulai' => 'date',
        'aktif'         => 'boolean',
    ];

    public function pesantren(): BelongsTo
    {
        return $this->belongsTo(Pesantren::class);
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class);
    }

    public function ekskulMaster(): BelongsTo
    {
        return $this->belongsTo(EkskulMaster::class, 'ekskul_id');
    }

    public function labelLevel(): string
    {
        return match ($this->level) {
            'pemula'   => 'Pemula',
            'menengah' => 'Menengah',
            'mahir'    => 'Mahir',
            default    => ucfirst($this->level),
        };
    }
}
