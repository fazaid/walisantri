<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\EkskulMaster;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Satu-satunya sumber jawaban atas "apa yang dipegang ustadz ini".
 *
 * Jenis ustadz (pembimbing, pengampu, penguji, pembina, wali kelas) adalah
 * PENUGASAN, bukan role: satu orang lazim merangkap beberapa sekaligus, jadi
 * semuanya disimpan sebagai FK di entitas yang ditugaskan — bukan sebagai nilai
 * tambahan di users.role yang cuma muat satu nilai.
 *
 * Cakupan sengaja TERPISAH PER MODUL: pengampu hanya menjangkau nilai mapel yang
 * ia ampu, pembimbing hanya santri binaannya, wali kelas hanya kelasnya. Kelas ini
 * memusatkan definisinya supaya keenam jalur tidak lagi dihitung ulang ad-hoc di
 * tiap resource — bukan untuk menyatukan cakupannya jadi satu kolam.
 */
class PenugasanUstadz
{
    private static function userId(?int $userId): ?int
    {
        return $userId ?? Auth::id();
    }

    /** Santri yang dibimbing langsung — santri.pembimbing_ustadz_id */
    public static function santriIdsBimbingan(?int $userId = null): Collection
    {
        return Santri::where('pembimbing_ustadz_id', self::userId($userId))->pluck('id');
    }

    /** Kelas tempat ia mengampu mapel — mata_pelajaran.ustadz_id */
    public static function kelasIdsDiampu(?int $userId = null): Collection
    {
        return MataPelajaran::where('ustadz_id', self::userId($userId))
            ->pluck('kelas_id')
            ->unique()
            ->values();
    }

    /** Mapel yang ia ampu — dipakai men-scope nilai akademik */
    public static function mataPelajaranIdsDiampu(?int $userId = null): Collection
    {
        return MataPelajaran::where('ustadz_id', self::userId($userId))->pluck('id');
    }

    /** Kelas yang ia walikan — kelas.wali_kelas_id (fondasi modul absensi) */
    public static function kelasIdsPerwalian(?int $userId = null): Collection
    {
        return Kelas::where('wali_kelas_id', self::userId($userId))->pluck('id');
    }

    /**
     * Santri di kelas yang ia walikan — fondasi cakupan presensi harian.
     *
     * Turunan murni dari kelasIdsPerwalian(): tidak ada kolom baru, jadi tidak bisa
     * basi. Sengaja TIDAK digabung dengan santriIdsBimbingan() — pembimbing halaqah
     * tidak punya akses presensi sama sekali (§5.4), dan menggabungkannya di sini
     * adalah cara paling mudah untuk melebarkan cakupan tanpa sadar.
     */
    public static function santriIdsPerwalianKelas(?int $userId = null): Collection
    {
        return Santri::whereIn('kelas_id', self::kelasIdsPerwalian($userId))->pluck('id');
    }

    /** Ekskul yang ia bina — ekskul_masters.pembina_id */
    public static function ekskulIdsDibina(?int $userId = null): Collection
    {
        return EkskulMaster::where('pembina_id', self::userId($userId))->pluck('id');
    }

    /**
     * Ringkasan penugasan untuk ditampilkan di halaman Pengguna, mis.
     * ["Pembimbing 12 santri", "Wali Kelas 3A", "Pengampu Fiqih 3A"].
     *
     * Murni turunan dari FK — tidak ada kolom yang disimpan, jadi tidak bisa basi.
     */
    public static function ringkasan(User $user): array
    {
        // Empat query per pemanggilan, dan pemanggilnya adalah kolom tabel yang jalan
        // SEKALI PER BARIS. Wali santri (baris terbanyak — satu per keluarga) dan super
        // admin tidak pernah bisa jadi pembimbing/wali kelas/pengampu/pembina, jadi
        // keluar lebih awal: halaman Pengguna dengan 25 baris tidak lagi menembak
        // ~100 query hanya demi satu kolom.
        if (in_array($user->role, [UserRole::WaliSantri->value, UserRole::SuperAdmin->value], true)) {
            return [];
        }

        $ringkasan = [];

        $jumlahBimbingan = Santri::where('pembimbing_ustadz_id', $user->id)
            ->where('status_aktif', true)
            ->count();

        if ($jumlahBimbingan > 0) {
            $ringkasan[] = "Pembimbing {$jumlahBimbingan} santri";
        }

        $perwalian = Kelas::where('wali_kelas_id', $user->id)->pluck('nama_kelas');
        foreach ($perwalian as $namaKelas) {
            $ringkasan[] = "Wali Kelas {$namaKelas}";
        }

        $mapel = MataPelajaran::where('ustadz_id', $user->id)
            ->with('kelas')
            ->get()
            ->map(fn (MataPelajaran $m) => trim($m->nama_mapel.' '.($m->kelas?->nama_kelas ?? '')));

        if ($mapel->isNotEmpty()) {
            $ringkasan[] = 'Pengampu '.$mapel->implode(', ');
        }

        $ekskul = EkskulMaster::where('pembina_id', $user->id)->pluck('nama');
        if ($ekskul->isNotEmpty()) {
            $ringkasan[] = 'Pembina '.$ekskul->implode(', ');
        }

        return $ringkasan;
    }
}
