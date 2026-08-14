<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPesantren;
use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Table('master_pengumuman')]
#[Fillable([
    'pesantren_id',
    'judul_maklumat',
    'isi_maklumat',
    'target_audience',
])]
class MasterPengumuman extends Model
{
    use BelongsToPesantren, Multitenantable;

    /**
     * pesantren_id nullable sejak tenant/2026_05_21_000002 — NULL berarti pengumuman
     * global yang ditulis super admin dan berlaku untuk semua pesantren. Tanpa ini,
     * guard di Multitenantable memblokir super admin membuat pengumuman global sama
     * sekali (ValidationException ke key pesantren_id yang tak ada di form, sehingga
     * modal gagal simpan tanpa pesan yang terlihat).
     */
    public static function bolehTanpaPesantren(): bool
    {
        return true;
    }

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
}
