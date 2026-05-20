<?php

// File: app/Models/Pesantren.php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('pesantrens')]
#[Fillable([
    'nama_pesantren',
    'slug',
    'paket_langganan',
    'max_santri_kuota',
    'status_berlangganan',
    'expired_at',
])]
class Pesantren extends Model
{
    protected function casts(): array
    {
        return [
            'expired_at' => 'datetime',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function santri(): HasMany
    {
        return $this->hasMany(Santri::class);
    }

    // Helper: cek apakah tenant masih aktif
    public function isActive(): bool
    {
        return in_array($this->status_berlangganan, ['trial', 'active'])
            && ($this->expired_at === null || $this->expired_at->isFuture());
    }

    // Helper: cek apakah kuota santri aktif sudah penuh
    public function isQuotaFull(): bool
    {
        return $this->santri()->where('status_aktif', true)->count()
            >= $this->max_santri_kuota;
    }
}