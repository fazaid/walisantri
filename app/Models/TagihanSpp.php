<?php

namespace App\Models;

use App\Enums\StatusTagihanSpp;
use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TagihanSpp extends Model
{
    use Multitenantable;

    protected $table = 'tagihan_spp';

    protected $fillable = [
        'pesantren_id',
        'santri_id',
        'bulan',
        'tahun',
        'nominal',
        'jatuh_tempo',
        'keterangan',
        'status',
    ];

    protected $casts = [
        'bulan'        => 'integer',
        'tahun'        => 'integer',
        'nominal'      => 'integer',
        'jatuh_tempo'  => 'date',
        'status'       => StatusTagihanSpp::class,
    ];

    public static array $namaBulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function getLabelPeriodeAttribute(): string
    {
        return (self::$namaBulan[$this->bulan] ?? $this->bulan) . ' ' . $this->tahun;
    }

    public function getNominalRpAttribute(): string
    {
        return 'Rp ' . number_format($this->nominal, 0, ',', '.');
    }

    public function isLunas(): bool
    {
        return $this->status === StatusTagihanSpp::Lunas;
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class);
    }

    public function pesantren(): BelongsTo
    {
        return $this->belongsTo(Pesantren::class);
    }

    public function pembayaran(): HasOne
    {
        return $this->hasOne(PembayaranSpp::class);
    }
}
