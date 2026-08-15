<?php

namespace App\Models;

use App\Enums\JenisIzin;
use App\Enums\StatusPengajuanIzin;
use App\Models\Concerns\BelongsToPesantren;
use App\Models\Concerns\BelongsToSantri;
use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// #[Table] WAJIB — tanpa ini Laravel menebak tabelnya 'presensi_izins'.
#[Table('presensi_izin')]
#[Fillable([
    'pesantren_id',
    'santri_id',
    'jenis',
    'tanggal_mulai',
    'tanggal_selesai',
    'alasan',
    'lampiran',
    'status',
    'diajukan_oleh',
    'diproses_oleh',
    'diproses_at',
    'catatan_petugas',
])]
class PresensiIzin extends Model
{
    use BelongsToPesantren, BelongsToSantri, Multitenantable;

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'jenis' => JenisIzin::class,
            'status' => StatusPengajuanIzin::class,
            'diproses_at' => 'datetime',
        ];
    }

    public function presensi(): HasMany
    {
        return $this->hasMany(Presensi::class);
    }

    public function menungguPersetujuan(): bool
    {
        return $this->status === StatusPengajuanIzin::Diajukan;
    }

    public function sudahDisetujui(): bool
    {
        return $this->status === StatusPengajuanIzin::Disetujui;
    }

    /** Diajukan wali lewat portal, bukan dicatat langsung oleh admin. */
    public function dariWali(): bool
    {
        return $this->diajukan_oleh !== null;
    }

    /**
     * Izin lain milik santri yang sama yang rentang tanggalnya beririsan.
     *
     * Tabel ini sengaja tanpa unique — "beririsan" bukan kesetaraan yang bisa
     * dinyatakan sebagai constraint. Tanpa penjagaan, dua izin yang beririsan akan
     * saling menimpa baris presensi dan hasil akhirnya bergantung urutan
     * persetujuan, yang tidak bisa ditebak siapa pun.
     *
     * Hanya izin yang masih hidup (diajukan/disetujui) yang dihitung; yang ditolak
     * atau dibatalkan tidak menulis presensi apa pun.
     *
     * @param  int|null  $kecualikanId  id izin yang sedang disunting
     */
    public static function beririsan(
        int $santriId,
        string $mulai,
        string $selesai,
        ?int $kecualikanId = null,
    ): Builder {
        return static::where('santri_id', $santriId)
            ->whereIn('status', [
                StatusPengajuanIzin::Diajukan->value,
                StatusPengajuanIzin::Disetujui->value,
            ])
            // Dua rentang beririsan bila masing-masing dimulai sebelum yang lain
            // berakhir. Bentuk ini menangkap semua kasus sekaligus — beririsan
            // sebagian di kedua ujung, dan rentang yang termuat seluruhnya.
            ->where('tanggal_mulai', '<=', $selesai)
            ->where('tanggal_selesai', '>=', $mulai)
            ->when($kecualikanId, fn (Builder $q) => $q->where('id', '!=', $kecualikanId));
    }
}
