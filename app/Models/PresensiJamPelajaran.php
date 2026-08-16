<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPesantren;
use App\Support\PresensiDefault;
use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

// #[Table] WAJIB — tanpa ini Laravel menebak tabelnya 'presensi_jam_pelajarans'.
#[Table('presensi_jam_pelajaran')]
#[Fillable([
    'pesantren_id',
    'jam_ke',
    'jam_mulai',
    'jam_selesai',
    'label',
    'aktif',
])]
class PresensiJamPelajaran extends Model
{
    use BelongsToPesantren, Multitenantable;

    protected function casts(): array
    {
        return [
            'jam_ke' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    /**
     * Label pilihan untuk satu jam, mis. "Jam ke-3 (08:30–09:15)".
     *
     * Waktunya ikut ditampilkan karena "jam ke-3" saja tidak cukup bagi ustadz
     * yang baru masuk kelas: yang ia tahu adalah pukul berapa sekarang.
     */
    public function labelPilihan(): string
    {
        $rentang = substr((string) $this->jam_mulai, 0, 5).'–'.substr((string) $this->jam_selesai, 0, 5);

        return $this->label
            ? "Jam ke-{$this->jam_ke} · {$this->label} ({$rentang})"
            : "Jam ke-{$this->jam_ke} ({$rentang})";
    }

    /**
     * Jam aktif milik satu pesantren, dibuatkan bila belum ada sama sekali.
     *
     * Lapis penyembuh KETIGA, sepola PresensiPengaturan::untuk(): ProvisionTenant
     * mengisi tenant baru, migrasi 2026_08_15_000010 menambal tenant lama, dan
     * method ini menutup sisa kemungkinan apa pun.
     *
     * Penyembuhan hanya terjadi saat pesantren belum punya SATU baris pun. Admin
     * yang sengaja menonaktifkan semua jam tidak akan dibanjiri delapan jam bawaan
     * lagi tiap kali halaman dibuka — hanya yang benar-benar kosong yang diisi.
     *
     * @return Collection<int, self>
     */
    public static function aktifUntuk(int $pesantrenId): Collection
    {
        $adaBaris = static::withoutGlobalScope('pesantren')
            ->where('pesantren_id', $pesantrenId)
            ->exists();

        if (! $adaBaris) {
            PresensiDefault::untukPesantren($pesantrenId);
        }

        return static::withoutGlobalScope('pesantren')
            ->where('pesantren_id', $pesantrenId)
            ->where('aktif', true)
            ->orderBy('jam_ke')
            ->get();
    }
}
