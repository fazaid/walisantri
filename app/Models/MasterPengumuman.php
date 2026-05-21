<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('master_pengumuman')]
#[Fillable([
    'pesantren_id',
    'judul_maklumat',
    'isi_maklumat',
    'target_audience',
])]
class MasterPengumuman extends Model
{
    use Multitenantable;

    protected function casts(): array
    {
        return [
            'target_audience' => 'string',
        ];
    }

    // Scope: pengumuman yang relevan untuk Admin & Ustadz
    public function scopeForAdmin(Builder $query): Builder
    {
        return $query->whereIn('target_audience', ['admin', 'semua']);
    }

    // Scope: pengumuman yang relevan untuk Wali Santri
    public function scopeForWali(Builder $query): Builder
    {
        return $query->whereIn('target_audience', ['wali', 'semua']);
    }

    public function pesantren(): BelongsTo
    {
        return $this->belongsTo(Pesantren::class);
    }
}
