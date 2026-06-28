<?php

namespace App\Models;

use App\Enums\JenisUangSaku;
use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UangSakuSantri extends Model
{
    use Multitenantable;

    protected $table = 'uang_saku_santri';

    protected $fillable = [
        'pesantren_id',
        'santri_id',
        'jenis',
        'nominal',
        'tanggal',
        'keterangan',
        'dicatat_oleh',
    ];

    protected $casts = [
        'nominal' => 'integer',
        'jenis'   => JenisUangSaku::class,
        'tanggal' => 'date',
    ];

    public function getNominalRpAttribute(): string
    {
        return 'Rp ' . number_format($this->nominal, 0, ',', '.');
    }

    public static function getSaldo(int $santriId): int
    {
        $rows = static::withoutGlobalScope('pesantren')
            ->where('santri_id', $santriId)
            ->selectRaw("jenis, SUM(nominal) as total")
            ->groupBy('jenis')
            ->pluck('total', 'jenis');

        return ($rows[JenisUangSaku::Setoran->value] ?? 0)
             - ($rows[JenisUangSaku::Pengambilan->value] ?? 0);
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class);
    }

    public function pesantren(): BelongsTo
    {
        return $this->belongsTo(Pesantren::class);
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }
}
