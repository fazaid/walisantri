<?php

// File: app/Models/Santri.php

namespace App\Models;

use App\Enums\JenisKelamin;
use App\Models\Concerns\BelongsToPesantren;
use App\Support\PenugasanUstadz;
use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

#[Table('santri')]
#[Fillable([
    'pesantren_id',
    'wali_santri_id',
    'pembimbing_ustadz_id',
    'nis',
    'nama_lengkap',
    'nama_panggilan',
    'tanggal_lahir',
    'jenis_kelamin',
    'nama_ayah',
    'nama_ibu',
    'alamat_lengkap',
    'jumlah_saudara',
    'ciri_fisik',
    'cita_cita',
    'kelas_id',
    'kamar_id',
    'status_aktif',
    'foto_profil',
    'kode_presensi',
    'kode_presensi_diperbarui_at',
])]
#[Hidden(['pesantren_id'])]
class Santri extends Model
{
    use BelongsToPesantren, HasFactory, HasUuids, Multitenantable, SoftDeletes;

    // Batasi HasUuids hanya pada kolom 'uuid', bukan 'id'
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'jenis_kelamin' => JenisKelamin::class,
        ];
    }

    public function getFotoProfilUrlAttribute(): ?string
    {
        return $this->foto_profil
            ? Storage::disk('public')->url($this->foto_profil)
            : null;
    }

    /**
     * @deprecated Pakai PenugasanUstadz::santriIdsBimbingan() — definisi cakupan
     *             ustadz dipusatkan di sana bersama jalur penugasan lainnya.
     */
    public static function idsPembimbing(int $ustadzId): Collection
    {
        return PenugasanUstadz::santriIdsBimbingan($ustadzId);
    }

    /**
     * Link portal wali (magic link). Permanen sampai UUID di-regenerasi lewat
     * RegenerasiUuidAction. Dipakai modal "Link Wali" dan kolom Link Wali di
     * daftar santri — keduanya harus menghasilkan URL yang sama persis.
     */
    public function linkWali(): string
    {
        $appDomain = config('app.domain', 'app.walisantri.com');
        $scheme = app()->environment('production') ? 'https' : 'http';

        return "{$scheme}://{$appDomain}/report/{$this->uuid}";
    }

    // --- Relations ---

    public function wali(): BelongsTo
    {
        return $this->belongsTo(User::class, 'wali_santri_id');
    }

    public function pembimbing(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pembimbing_ustadz_id');
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function kamar(): BelongsTo
    {
        return $this->belongsTo(Kamar::class);
    }

    public function tagihanSpp(): HasMany
    {
        return $this->hasMany(TagihanSpp::class)->withoutGlobalScope('pesantren');
    }

    public function prestasi(): HasMany
    {
        return $this->hasMany(PrestasiSantri::class)->withoutGlobalScope('pesantren');
    }

    public function ekskulSantri(): HasMany
    {
        return $this->hasMany(SantriEkskul::class);
    }

    public function uangSaku(): HasMany
    {
        return $this->hasMany(UangSakuSantri::class)->withoutGlobalScope('pesantren');
    }
}
