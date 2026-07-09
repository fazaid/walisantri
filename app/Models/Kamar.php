<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPesantren;
use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('kamar')]
#[Fillable(['pesantren_id', 'nama_kamar', 'kapasitas'])]
class Kamar extends Model
{
    use BelongsToPesantren, Multitenantable;

    public function santri(): HasMany
    {
        return $this->hasMany(Santri::class);
    }
}
