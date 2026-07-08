<?php

// File: app/Models/Pesantren.php

namespace App\Models;

use App\Enums\OnboardingStep;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

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

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function activeOrder(): HasOne
    {
        return $this->hasOne(Order::class)
            ->whereIn('status', ['pending_payment', 'awaiting_confirmation'])
            ->latestOfMany();
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

    // Helper: jumlah santri aktif — dipakai untuk statistik ringkas di profil publik
    public function jumlahSantriAktif(): int
    {
        return $this->santri()->where('status_aktif', true)->count();
    }

    public function completeOnboardingStep(OnboardingStep $step): void
    {
        $steps = $this->onboarding_completed_steps ?? [];

        if (in_array($step->value, $steps, true)) {
            return;
        }

        $steps[] = $step->value;

        // saveQuietly(): tidak fire event Eloquent -> mencegah PesantrenObserver::updated()
        // terpanggil rekursif saat method ini dipanggil dari dalam updated() itu sendiri.
        $this->forceFill(['onboarding_completed_steps' => $steps])->saveQuietly();
    }

    public function hasCompletedOnboardingStep(OnboardingStep $step): bool
    {
        return in_array($step->value, $this->onboarding_completed_steps ?? [], true);
    }

    public function isOnboardingComplete(): bool
    {
        foreach (OnboardingStep::required() as $step) {
            if (! $this->hasCompletedOnboardingStep($step)) {
                return false;
            }
        }

        return true;
    }

    public function getLogoUrlAttribute(): ?string
    {
        $path = $this->profil['logo'] ?? null;

        return $path ? Storage::disk('public')->url($path) : null;
    }

    public function getGaleriUrlsAttribute(): array
    {
        return collect($this->profil['galeri'] ?? [])
            ->map(fn (string $path) => Storage::disk('public')->url($path))
            ->all();
    }

    // Path filesystem absolut logo — dipakai render PDF (DomPDF, enable_remote=false, tak bisa fetch URL)
    public function getLogoPathAttribute(): ?string
    {
        $path = $this->profil['logo'] ?? null;

        if (! $path) {
            return null;
        }

        $fullPath = Storage::disk('public')->path($path);

        return file_exists($fullPath) ? $fullPath : null;
    }
}