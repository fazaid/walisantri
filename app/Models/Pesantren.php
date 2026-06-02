<?php

// File: app/Models/Pesantren.php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    'santri_count_cache',
    'onboarding_completed_steps',
    'profil',
])]
class Pesantren extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'expired_at'                 => 'datetime',
            'santri_count_cache'         => 'integer',
            'onboarding_completed_steps' => 'array',
            'profil'                     => 'array',
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

    public function domains(): HasMany
    {
        return $this->hasMany(TenantDomain::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
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