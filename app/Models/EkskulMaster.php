<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPesantren;
use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('ekskul_masters')]
#[Fillable(['pesantren_id', 'nama', 'deskripsi', 'pembina_id', 'pengajar', 'aktif'])]
class EkskulMaster extends Model
{
    use BelongsToPesantren, HasFactory, Multitenantable;

    public function santriEkskuls(): HasMany
    {
        return $this->hasMany(SantriEkskul::class, 'ekskul_id');
    }

    public function pembina(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pembina_id');
    }

    /**
     * Nama pembina untuk ditampilkan: ustadz internal kalau tertaut akun, kalau
     * tidak jatuh ke teks bebas `pengajar` (pelatih luar / data lama sebelum
     * kolom pembina_id ada). Satu-satunya tempat logika fallback ini hidup.
     */
    public function namaPembina(): ?string
    {
        return $this->pembina?->name ?? ($this->pengajar ?: null);
    }
}
