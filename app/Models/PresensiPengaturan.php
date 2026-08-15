<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPesantren;
use App\Support\Waktu;
use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

// #[Table] WAJIB — tanpa ini Laravel menebak tabelnya 'presensi_pengaturans'.
#[Table('presensi_pengaturan')]
#[Fillable([
    'pesantren_id',
    'presensi_per_jam_aktif',
    'jam_masuk',
    'toleransi_terlambat_menit',
    'hari_libur_mingguan',
    'batas_edit_ustadz_hari',
    'izin_wali_aktif',
    'qr_aktif',
])]
class PresensiPengaturan extends Model
{
    use BelongsToPesantren, Multitenantable;

    protected function casts(): array
    {
        return [
            'presensi_per_jam_aktif' => 'boolean',
            'toleransi_terlambat_menit' => 'integer',
            'hari_libur_mingguan' => 'array',
            'batas_edit_ustadz_hari' => 'integer',
            'izin_wali_aktif' => 'boolean',
            'qr_aktif' => 'boolean',
        ];
    }

    /**
     * Pengaturan milik satu pesantren, dibuatkan bila belum ada.
     *
     * Ini lapis penyembuh KETIGA, dan ketiganya disengaja: ProvisionTenant mengisi
     * untuk tenant baru, migrasi 2026_08_15_000004 menambal tenant lama, dan method
     * ini menutup sisa kemungkinan apa pun. Modul Mutaba'ah pernah lumpuh diam-diam
     * berbulan-bulan justru karena satu-satunya pengisi datanya adalah migrasi yang
     * hanya jalan sekali (PRD §22, kelas bug v4.21) — presensi tidak boleh mengulanginya.
     *
     * pesantren_id diisi EKSPLISIT, bukan mengandalkan auto-assign Multitenantable:
     * selain lebih jelas, itu juga membuat method ini tetap bekerja saat dipanggil
     * dari konteks tanpa sesi (ProvisionTenant dipanggil saat registrasi publik).
     */
    public static function untuk(int $pesantrenId): self
    {
        $pengaturan = static::withoutGlobalScope('pesantren')
            ->firstOrCreate(['pesantren_id' => $pesantrenId]);

        // Nilai default kolom hidup di DB, bukan di model, jadi instance hasil
        // firstOrCreate() baru berisi pesantren_id saja — sisanya null sampai
        // dibaca ulang. Tanpa refresh ini, pemanggil pertama setelah baris dibuat
        // akan melihat toleransi_terlambat_menit = null dan batas edit = null.
        return $pengaturan->wasRecentlyCreated ? $pengaturan->refresh() : $pengaturan;
    }

    /**
     * Tanggal paling awal yang masih boleh disunting ustadz, atau null bila tanpa batas.
     *
     * Dipakai dua kali dan itu memang niatnya: sebagai ->minDate() di DatePicker,
     * dan sebagai pengecekan ulang di save(). Lapis kedua wajib — minDate hanyalah
     * validasi form yang bisa dilewati request Livewire yang dirakit tangan.
     */
    public function batasAwalEditUstadz(): ?string
    {
        if ($this->batas_edit_ustadz_hari <= 0) {
            return null;
        }

        return Waktu::sekarang()
            ->subDays($this->batas_edit_ustadz_hari - 1)
            ->toDateString();
    }
}
