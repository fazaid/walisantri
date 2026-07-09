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

#[Table('santri_ekskuls')]
#[Fillable(['pesantren_id', 'santri_id', 'ekskul_id', 'level', 'tanggal_mulai', 'aktif'])]
class SantriEkskul extends Model
{
    use BelongsToPesantren, BelongsToSantri, HasFactory, Multitenantable;

    protected $casts = [
        'tanggal_mulai' => 'date',
        'aktif' => 'boolean',
    ];

    public function ekskulMaster(): BelongsTo
    {
        return $this->belongsTo(EkskulMaster::class, 'ekskul_id');
    }

    public function labelLevel(): string
    {
        return match ($this->level) {
            'pemula' => 'Pemula',
            'menengah' => 'Menengah',
            'mahir' => 'Mahir',
            default => ucfirst($this->level),
        };
    }
}
