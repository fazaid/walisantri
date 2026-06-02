<?php

namespace App\Models;

use App\Enums\TipeDiskon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('kupons')]
#[Fillable([
    'kode',
    'tipe_diskon',
    'nilai_diskon',
    'min_durasi_bulan',
    'max_penggunaan',
    'jumlah_dipakai',
    'berlaku_hingga',
    'is_aktif',
    'catatan',
])]
class Kupon extends Model
{
    protected function casts(): array
    {
        return [
            'tipe_diskon'    => TipeDiskon::class,
            'berlaku_hingga' => 'datetime',
            'is_aktif'       => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isValid(int $durasibulan): bool
    {
        if (! $this->is_aktif) {
            return false;
        }

        if ($this->berlaku_hingga && $this->berlaku_hingga->isPast()) {
            return false;
        }

        if ($this->max_penggunaan !== null && $this->jumlah_dipakai >= $this->max_penggunaan) {
            return false;
        }

        if ($this->min_durasi_bulan !== null && $durasibulan < $this->min_durasi_bulan) {
            return false;
        }

        return true;
    }

    public function hitungDiskon(int $hargaTotal): int
    {
        if ($this->tipe_diskon === TipeDiskon::Persentase) {
            return (int) round($hargaTotal * $this->nilai_diskon / 100);
        }

        return min($this->nilai_diskon, $hargaTotal);
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_aktif', true)
            ->where(fn (Builder $q) => $q
                ->whereNull('berlaku_hingga')
                ->orWhere('berlaku_hingga', '>=', now())
            );
    }
}
