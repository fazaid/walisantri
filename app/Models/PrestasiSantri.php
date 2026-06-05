<?php

namespace App\Models;

use App\Enums\TingkatPrestasi;
use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PrestasiSantri extends Model
{
    use Multitenantable;

    protected $table = 'prestasi_santri';

    protected $fillable = [
        'pesantren_id',
        'santri_id',
        'judul',
        'kategori',
        'tingkat',
        'posisi',
        'tanggal',
        'penyelenggara',
        'keterangan',
        'dokumen',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tingkat' => TingkatPrestasi::class,
    ];

    public static array $kategoriOptions = [
        'Tahfidz Al-Quran'    => 'Tahfidz Al-Quran',
        'Tilawah Al-Quran'    => 'Tilawah Al-Quran',
        'Akademik'            => 'Akademik',
        'Pidato / Muhadharah' => 'Pidato / Muhadharah',
        'Kaligrafi'           => 'Kaligrafi',
        'Olahraga'            => 'Olahraga',
        'Seni & Budaya'       => 'Seni & Budaya',
        'Debat / Olimpiade'   => 'Debat / Olimpiade',
        'Lainnya'             => 'Lainnya',
    ];

    public static array $posisiOptions = [
        'Juara 1'        => 'Juara 1',
        'Juara 2'        => 'Juara 2',
        'Juara 3'        => 'Juara 3',
        'Harapan 1'      => 'Harapan 1',
        'Harapan 2'      => 'Harapan 2',
        'Harapan 3'      => 'Harapan 3',
        'Peserta Terbaik'=> 'Peserta Terbaik',
        'Finalis'        => 'Finalis',
    ];

    public function getDokumenUrlAttribute(): ?string
    {
        return $this->dokumen
            ? Storage::disk('public')->url($this->dokumen)
            : null;
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class);
    }

    public function pesantren(): BelongsTo
    {
        return $this->belongsTo(Pesantren::class);
    }
}
