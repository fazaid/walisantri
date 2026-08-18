<?php

// File: app/Models/Pesantren.php

namespace App\Models;

use App\Enums\OnboardingStep;
use App\Enums\StatusBerlangganan;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

#[Table('pesantrens')]
#[Fillable([
    'nama_pesantren',
    'slug',
    'is_demo',
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
            'expired_at' => 'datetime',
            'is_demo' => 'boolean',
            'santri_count_cache' => 'integer',
            'onboarding_completed_steps' => 'array',
            'profil' => 'array',
        ];
    }

    /**
     * Hanya pesantren pelanggan — tenant sandbox publik (is_demo) dikecualikan.
     *
     * Dipakai setiap hitungan & daftar di dashboard super admin: tenant demo
     * yang ikut terhitung membuat angka pertumbuhan berbohong ke diri sendiri.
     */
    public function scopePelanggan(Builder $query): Builder
    {
        return $query->where('is_demo', false);
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

    /**
     * Hostname yang melayani pesantren ini (§1.8 Fase 1) — satu-satunya tempat
     * host tenant diturunkan, supaya magic link, portal wali, dan pengalihan
     * pintu kanonik tidak pernah menghasilkan alamat yang berbeda.
     *
     * Diambil dari `tenant_domains` (baris `is_primary`), BUKAN dirakit dari slug:
     * hostname adalah alamat yang benar-benar dilayani, slug hanya bahan bakunya
     * dan bisa berganti. Fallback ke host platform saat tenant belum punya baris
     * domain — pintu kanonik di app host tetap melayani dan mengalihkan.
     */
    public function hostname(): string
    {
        return $this->domains()->where('is_primary', true)->value('hostname')
            ?? config('app.domain', 'app.walisantri.com');
    }

    /**
     * URL absolut di host pesantren ini. Aman dipakai dari konteks tanpa request
     * (job queue, perintah artisan) — tidak menyentuh host request sama sekali.
     */
    public function url(string $path = '/'): string
    {
        $skema = app()->environment('production') ? 'https' : 'http';

        return $skema.'://'.$this->hostname().'/'.ltrim($path, '/');
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
        return in_array($this->status_berlangganan, StatusBerlangganan::berjalan(), true)
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

    /**
     * Lokasi lengkap untuk ditampilkan: alamat jalan + wilayah administratif.
     *
     * Keduanya digabung, bukan salah satu, karena sejak v4.51 `/register` memisahkan
     * keduanya — `alamat` berisi jalan/RT-RW saja, dan menampilkannya sendirian akan
     * menghilangkan kota serta provinsi dari profil publik.
     *
     * Tenant lama (dan yang dibuat super admin) tidak punya `wilayah`, jadi mereka
     * tetap menampilkan alamat panjangnya apa adanya tanpa duplikasi.
     */
    public function alamatLengkap(): ?string
    {
        $bagian = array_filter([
            $this->profil['alamat'] ?? null,
            $this->alamatWilayah(),
        ]);

        return $bagian === [] ? null : implode(', ', $bagian);
    }

    /**
     * Wilayah administratif dari kolom yang diisi saat pendaftaran (§4.1), dirangkai
     * untuk dibaca manusia: "Desa, Kec. X, Kabupaten Y, Provinsi Z".
     */
    public function alamatWilayah(): ?string
    {
        $wilayah = $this->profil['wilayah'] ?? null;

        if (blank($wilayah['desa']['nama'] ?? null)) {
            return null;
        }

        return implode(', ', array_filter([
            $wilayah['desa']['nama'],
            filled($wilayah['kecamatan']['nama'] ?? null) ? 'Kec. '.$wilayah['kecamatan']['nama'] : null,
            $wilayah['kota']['nama'] ?? null,
            $wilayah['provinsi']['nama'] ?? null,
        ]));
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
