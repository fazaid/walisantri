<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPesantren;
use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('kelas')]
#[Fillable(['pesantren_id', 'nama_kelas'])]
class Kelas extends Model
{
    use BelongsToPesantren, HasFactory, Multitenantable;

    public function santri(): HasMany
    {
        return $this->hasMany(Santri::class);
    }
}
