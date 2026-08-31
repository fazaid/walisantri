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
     * Path filesystem absolut foto profil — khusus render PDF.
     *
     * DomPDF di proyek ini jalan dengan `enable_remote = false`, jadi ia tidak
     * bisa mengambil URL sama sekali. Memakai `foto_profil_url` di template PDF
     * tidak melempar error apa pun: fotonya sekadar tidak muncul. Cermin dari
     * `Pesantren::getLogoPathAttribute()`, termasuk guard file_exists()-nya —
     * baris DB bisa menunjuk berkas yang sudah lenyap dari disk.
     */
    public function getFotoProfilPathAttribute(): ?string
    {
        if (! $this->foto_profil) {
            return null;
        }

        $path = Storage::disk('public')->path($this->foto_profil);

        return file_exists($path) ? $path : null;
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
     *
     * Sejak §1.8 Fase 1 tautannya menunjuk host pesantren, bukan host platform.
     * Sengaja dibangun dari `tenant_domains` (hostname `is_primary`), BUKAN dari
     * slug: hostname adalah alamat yang benar-benar dilayani, sementara slug bisa
     * berganti. Dipakai juga di konteks queue — job WhatsApp/email berjalan tanpa
     * request, jadi ia tidak boleh bergantung pada host request.
     *
     * Fallback ke host platform ketika tenant belum punya baris domain: pintu
     * kanonik `app.../report/{uuid}` tetap melayani dan mengalihkan, jadi tautan
     * yang telanjur dibagikan tidak pernah mati.
     */
    public function linkWali(): string
    {
        $pesantren = $this->pesantren;

        if ($pesantren === null) {
            $skema = app()->environment('production') ? 'https' : 'http';

            return $skema.'://'.config('app.domain', 'app.walisantri.com')."/report/{$this->uuid}";
        }

        return $pesantren->url("/report/{$this->uuid}");
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
