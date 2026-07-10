<?php

namespace App\Models;

use App\Enums\PaketLangganan;
use App\Enums\StatusOrder;
use App\Models\Concerns\BelongsToPesantren;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Table('orders')]
#[Fillable([
    'pesantren_id',
    'kupon_id',
    'nomor_order',
    'paket_target',
    'durasi_bulan',
    'max_santri_kuota_target',
    'harga_per_bulan',
    'harga_total_sebelum_diskon',
    'diskon_nominal',
    'harga_total',
    'bonus_bulan',
    'durasi_total_bulan',
    'kode_kupon_snapshot',
    'status',
    'catatan_admin',
    'confirmed_at',
    'confirmed_by',
    'expired_at_baru',
])]
class Order extends Model
{
    use BelongsToPesantren;

    protected function casts(): array
    {
        return [
            'paket_target' => PaketLangganan::class,
            'status' => StatusOrder::class,
            'confirmed_at' => 'datetime',
            'expired_at_baru' => 'datetime',
        ];
    }

    public function kupon(): BelongsTo
    {
        return $this->belongsTo(Kupon::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function isPendingPayment(): bool
    {
        return $this->status === StatusOrder::PendingPayment;
    }

    public function isAwaitingConfirmation(): bool
    {
        return $this->status === StatusOrder::AwaitingConfirmation;
    }

    public function getFormattedHargaAttribute(): string
    {
        return 'Rp '.number_format($this->harga_total, 0, ',', '.');
    }

    /**
     * Tanggal mulai periode langganan yang dicakup order ini. Tidak pernah
     * disimpan langsung — diturunkan dari expired_at_baru (kalau order sudah
     * dikonfirmasi) atau diproyeksikan dari expired_at pesantren saat ini,
     * mirror persis logika di UpgradeOrderService::confirmOrder().
     */
    public function periodeMulai(): Carbon
    {
        if ($this->expired_at_baru) {
            return $this->expired_at_baru->copy()->subMonthsNoOverflow($this->durasi_total_bulan);
        }

        $pesantren = $this->pesantren;

        return ($pesantren?->expired_at && $pesantren->expired_at->isFuture())
            ? $pesantren->expired_at->copy()
            : now();
    }

    public function periodeSelesai(): Carbon
    {
        return $this->expired_at_baru
            ? $this->expired_at_baru->copy()
            : $this->periodeMulai()->copy()->addMonthsNoOverflow($this->durasi_total_bulan);
    }
}
