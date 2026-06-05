<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembayaranSpp extends Model
{
    use Multitenantable;

    protected $table = 'pembayaran_spp';

    protected $fillable = [
        'pesantren_id',
        'tagihan_spp_id',
        'jumlah',
        'tanggal_bayar',
        'metode_bayar',
        'catatan',
        'dicatat_oleh',
    ];

    protected $casts = [
        'jumlah'       => 'integer',
        'tanggal_bayar' => 'date',
    ];

    public static array $metodeBayar = [
        'tunai'         => 'Tunai',
        'transfer_bank' => 'Transfer Bank',
        'lainnya'       => 'Lainnya',
    ];

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(TagihanSpp::class, 'tagihan_spp_id');
    }

    public function dicatatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }
}
