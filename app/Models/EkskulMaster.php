<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('ekskul_masters')]
#[Fillable(['pesantren_id', 'nama', 'deskripsi', 'pengajar', 'aktif'])]
class EkskulMaster extends Model
{
    use HasFactory, Multitenantable;

    public function pesantren(): BelongsTo
    {
        return $this->belongsTo(Pesantren::class);
    }

    public function santriEkskuls(): HasMany
    {
        return $this->hasMany(SantriEkskul::class, 'ekskul_id');
    }
}
