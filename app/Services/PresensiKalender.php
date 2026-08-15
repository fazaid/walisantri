<?php

namespace App\Services;

use App\Models\PresensiHariLibur;
use App\Models\PresensiPengaturan;
use Illuminate\Support\Carbon;

/**
 * Satu-satunya sumber jawaban atas "hari ini sekolah atau tidak".
 *
 * Dua sumber libur digabung di sini supaya pemanggilnya tidak perlu tahu ada dua:
 * libur MINGGUAN (pola tetap, disimpan sebagai daftar hari di presensi_pengaturan)
 * dan libur KALENDER (tanggal tertentu, satu baris per hari di presensi_hari_libur).
 *
 * Dipakai halaman Isi Presensi untuk memperingatkan, dan nanti oleh Rekap sebagai
 * penyebut "hari efektif". Menaruhnya di satu tempat sejak sekarang mencegah kelas
 * masalah yang sudah pernah terjadi di modul rapor: halaman dan PDF punya versi
 * query sendiri, lalu menyimpang, dan menyimpangnya baru ketahuan setahun kemudian.
 */
class PresensiKalender
{
    /** @var array<string, string>|null tanggal (Y-m-d) => keterangan */
    private ?array $liburKalender = null;

    private function __construct(
        private readonly int $pesantrenId,
        private readonly PresensiPengaturan $pengaturan,
    ) {}

    public static function untuk(int $pesantrenId): self
    {
        return new self($pesantrenId, PresensiPengaturan::untuk($pesantrenId));
    }

    /**
     * Hari libur mingguan sebagai angka Carbon::dayOfWeek.
     *
     * ⚠️ 0 = MINGGU, 1 = Senin … 6 = Sabtu — BUKAN ISO-8601 (1 = Senin … 7 = Minggu).
     * Salah membacanya menggeser seluruh perhitungan satu hari tanpa error apa pun.
     *
     * @return list<int>
     */
    public function liburMingguan(): array
    {
        return array_map('intval', $this->pengaturan->hari_libur_mingguan ?? []);
    }

    public function adalahLiburMingguan(string $tanggal): bool
    {
        return in_array(Carbon::parse($tanggal)->dayOfWeek, $this->liburMingguan(), true);
    }

    public function adalahLibur(string $tanggal): bool
    {
        return $this->adalahLiburMingguan($tanggal) || $this->keteranganKalender($tanggal) !== null;
    }

    /**
     * Keterangan libur untuk ditampilkan ke pengguna, atau null bila hari sekolah.
     *
     * Libur kalender didahulukan: "Maulid Nabi" lebih berguna daripada "Libur Minggu"
     * saat keduanya kebetulan jatuh di hari yang sama.
     */
    public function keteranganLibur(string $tanggal): ?string
    {
        $kalender = $this->keteranganKalender($tanggal);

        if ($kalender !== null) {
            return $kalender;
        }

        if ($this->adalahLiburMingguan($tanggal)) {
            return 'Libur '.Carbon::parse($tanggal)->translatedFormat('l');
        }

        return null;
    }

    /**
     * Jumlah hari efektif dalam rentang — penyebut persentase kehadiran.
     *
     * Libur kalender diambil sekali untuk seluruh rentang, bukan satu query per hari:
     * rekap satu semester berarti ~180 iterasi, dan versi naifnya akan menembakkan
     * 180 query hanya untuk menghitung sebuah penyebut.
     */
    public function hariEfektif(string $awal, string $akhir): int
    {
        return count($this->tanggalEfektif($awal, $akhir));
    }

    /**
     * Daftar tanggal sekolah dalam rentang (inklusif kedua ujungnya).
     *
     * @return list<string> format Y-m-d
     */
    public function tanggalEfektif(string $awal, string $akhir): array
    {
        $mulai = Carbon::parse($awal)->startOfDay();
        $selesai = Carbon::parse($akhir)->startOfDay();

        if ($selesai->lt($mulai)) {
            return [];
        }

        $this->muatLiburKalender($mulai->toDateString(), $selesai->toDateString());

        $mingguan = $this->liburMingguan();
        $efektif = [];

        for ($tanggal = $mulai->copy(); $tanggal->lte($selesai); $tanggal->addDay()) {
            $ymd = $tanggal->toDateString();

            if (in_array($tanggal->dayOfWeek, $mingguan, true)) {
                continue;
            }

            if (isset($this->liburKalender[$ymd])) {
                continue;
            }

            $efektif[] = $ymd;
        }

        return $efektif;
    }

    private function keteranganKalender(string $tanggal): ?string
    {
        $ymd = Carbon::parse($tanggal)->toDateString();

        // Pemanggilan tunggal (mis. peringatan di halaman Isi Presensi) tidak perlu
        // memuat seluruh rentang — cukup satu baris.
        if ($this->liburKalender === null) {
            return PresensiHariLibur::withoutGlobalScope('pesantren')
                ->where('pesantren_id', $this->pesantrenId)
                ->whereDate('tanggal', $ymd)
                ->value('keterangan');
        }

        return $this->liburKalender[$ymd] ?? null;
    }

    private function muatLiburKalender(string $awal, string $akhir): void
    {
        $this->liburKalender = PresensiHariLibur::withoutGlobalScope('pesantren')
            ->where('pesantren_id', $this->pesantrenId)
            ->whereBetween('tanggal', [$awal, $akhir])
            ->get(['tanggal', 'keterangan'])
            ->mapWithKeys(fn (PresensiHariLibur $libur) => [
                $libur->tanggal->toDateString() => $libur->keterangan,
            ])
            ->all();
    }
}
