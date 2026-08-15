<?php

namespace App\Services;

use App\Enums\StatusKehadiran;
use App\Models\Presensi;
use App\Support\PenugasanUstadz;
use App\Support\Waktu;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Satu-satunya sumber agregasi rekap presensi.
 *
 * Dipakai bersama oleh halaman Rekap dan ekspor Excel — dan nanti oleh PDF rapor.
 * Menaruhnya di satu tempat sejak awal adalah pelajaran v4.19: halaman rapor dan
 * PDF-nya dulu punya versi query masing-masing, lalu menyimpang, dan menyimpangnya
 * baru ketahuan setahun kemudian.
 *
 * ⚠️ Agregasinya di SQL, BUKAN Collection. Kedua service di App\Services\Rapor
 * menarik seluruh baris ke memori lalu merekap dengan Collection — aman di sana
 * karena lingkupnya satu santri (~180 baris/semester), mustahil di sini: rekap satu
 * semester untuk 1.000 santri menyentuh ratusan ribu baris. Cetakan yang benar ada
 * di SaldoUangSakuPage dan Wali\TahfidzStatsController.
 */
class PresensiRekap
{
    private function __construct(
        private readonly int $pesantrenId,
        private readonly string $awal,
        private readonly string $akhir,
        private readonly ?int $kelasId,
        private readonly ?int $ustadzId,
    ) {}

    /**
     * @param  string  $awal  Y-m-d
     * @param  string  $akhir  Y-m-d — akan di-clamp ke hari ini, lihat batasAkhir()
     */
    public static function untuk(
        int $pesantrenId,
        string $awal,
        string $akhir,
        ?int $kelasId = null,
        ?int $ustadzId = null,
    ): self {
        return new self($pesantrenId, $awal, self::batasAkhir($akhir), $kelasId, $ustadzId);
    }

    /**
     * Rekap tidak boleh menghitung hari yang belum terjadi.
     *
     * Periode "Semester Ganjil" berakhir 31 Desember, tapi kalau rekapnya dibuka
     * pertengahan Agustus, seluruh sisa tahun akan masuk penyebut "hari efektif" —
     * dan persentase kehadiran setiap santri anjlok tanpa ada yang salah. Batas
     * atasnya karena itu selalu dipotong ke hari ini.
     */
    private static function batasAkhir(string $akhir): string
    {
        $hariIni = Waktu::hariIni();

        return $akhir > $hariIni ? $hariIni : $akhir;
    }

    public function rentang(): array
    {
        return [$this->awal, $this->akhir];
    }

    public function hariEfektif(): int
    {
        return PresensiKalender::untuk($this->pesantrenId)->hariEfektif($this->awal, $this->akhir);
    }

    /**
     * Satu baris per santri, lengkap dengan hitungan tiap status.
     *
     * Berangkat dari `santri`, bukan dari `presensi` — santri yang belum pernah
     * diabsen sama sekali HARUS tetap muncul, justru merekalah yang paling perlu
     * terlihat. Santri yang di-soft-delete dikecualikan (keputusan v4.26, dicatat
     * di §22 sebagai batas yang diketahui).
     *
     * @return Collection<int, object>
     */
    public function perSantri(): Collection
    {
        $hariEfektif = $this->hariEfektif();
        $hadirEfektif = $this->statusHadirEfektif();

        $query = DB::table('santri')
            ->leftJoin('presensi as p', function ($join): void {
                $join->on('p.santri_id', '=', 'santri.id')
                    ->where('p.jam_ke', '=', Presensi::HARIAN)
                    ->whereBetween('p.tanggal', [$this->awal, $this->akhir]);
            })
            ->leftJoin('kelas', 'kelas.id', '=', 'santri.kelas_id')
            ->where('santri.pesantren_id', $this->pesantrenId)
            ->where('santri.status_aktif', true)
            // Query builder tidak mengenal global scope SoftDeletes; tanpa baris ini
            // santri yang sudah dihapus ikut terhitung (pola SaldoUangSakuPage).
            ->whereNull('santri.deleted_at')
            ->groupBy('santri.id', 'santri.nama_lengkap', 'kelas.nama_kelas')
            ->orderBy('kelas.nama_kelas')
            ->orderBy('santri.nama_lengkap');

        if ($this->kelasId !== null) {
            $query->where('santri.kelas_id', $this->kelasId);
        }

        if ($this->ustadzId !== null) {
            $query->whereIn('santri.kelas_id', PenugasanUstadz::kelasIdsPerwalian($this->ustadzId));
        }

        $kolom = [
            'santri.id',
            'santri.nama_lengkap',
            'kelas.nama_kelas',
            DB::raw('COUNT(p.id) AS total_tercatat'),
        ];

        foreach (StatusKehadiran::cases() as $status) {
            $kolom[] = DB::raw(
                "COALESCE(SUM(CASE WHEN p.status = '{$status->value}' THEN 1 ELSE 0 END), 0) AS ".$this->kolomStatus($status)
            );
        }

        return collect($query->select($kolom)->get())
            ->map(function (object $baris) use ($hariEfektif, $hadirEfektif): object {
                $tercatat = (int) $baris->total_tercatat;

                // Hari efektif yang tidak punya baris presensi sama sekali. SENGAJA
                // bukan dihitung sebagai Alpa: sistem tidak bisa membedakan "santri
                // bolos" dari "ustadz lupa mengisi", dan menebaknya berarti menuduh
                // (§11 — tidak ada job penanda Alpa otomatis).
                $baris->tanpa_keterangan = max(0, $hariEfektif - $tercatat);

                $hadir = 0;
                foreach ($hadirEfektif as $status) {
                    $hadir += (int) $baris->{$this->kolomStatus($status)};
                }

                $baris->hadir_efektif = $hadir;
                $baris->hari_efektif = $hariEfektif;
                $baris->persen_kehadiran = $hariEfektif > 0
                    ? (int) round($hadir / $hariEfektif * 100)
                    : 0;

                return $baris;
            });
    }

    /** Ringkasan seluruh santri dalam rentang — untuk kartu di atas tabel. */
    public function ringkasan(): object
    {
        $rows = $this->perSantri();

        return (object) [
            'jumlah_santri' => $rows->count(),
            'hari_efektif' => $this->hariEfektif(),
            'tanpa_keterangan' => (int) $rows->sum('tanpa_keterangan'),
            'persen_kehadiran' => $rows->isEmpty() ? 0 : (int) round($rows->avg('persen_kehadiran')),
        ];
    }

    /**
     * Santri dengan alpa beruntun minimal N kali — panel "Perlu Perhatian".
     *
     * "Berturut-turut" dihitung atas HARI EFEKTIF, bukan hari kalender: alpa Jumat
     * lalu alpa Senin adalah dua kali berturut-turut kalau Sabtu–Minggu libur.
     * Menghitungnya atas hari kalender akan memutus rangkaian setiap akhir pekan
     * dan membuat panel ini nyaris tidak pernah menyala.
     *
     * Dihitung di PHP dan itu disengaja — barisnya hanya yang berstatus Alpa
     * (jarang), jadi jumlahnya kecil; sementara mendeteksi rangkaian di SQL butuh
     * window function yang jauh lebih sulit dibaca daripada nilainya.
     *
     * @return Collection<int, object>
     */
    public function alpaBeruntun(int $minimal = 3): Collection
    {
        $hariEfektif = PresensiKalender::untuk($this->pesantrenId)
            ->tanggalEfektif($this->awal, $this->akhir);

        if ($hariEfektif === []) {
            return collect();
        }

        $query = DB::table('presensi as p')
            ->join('santri', 'santri.id', '=', 'p.santri_id')
            ->leftJoin('kelas', 'kelas.id', '=', 'santri.kelas_id')
            ->where('p.pesantren_id', $this->pesantrenId)
            ->where('p.jam_ke', Presensi::HARIAN)
            ->where('p.status', StatusKehadiran::Alpa->value)
            ->whereBetween('p.tanggal', [$this->awal, $this->akhir])
            ->where('santri.status_aktif', true)
            ->whereNull('santri.deleted_at');

        if ($this->kelasId !== null) {
            $query->where('santri.kelas_id', $this->kelasId);
        }

        if ($this->ustadzId !== null) {
            $query->whereIn('santri.kelas_id', PenugasanUstadz::kelasIdsPerwalian($this->ustadzId));
        }

        $alpa = $query
            ->select(['p.santri_id', 'p.tanggal', 'santri.nama_lengkap', 'kelas.nama_kelas'])
            ->get()
            ->groupBy('santri_id');

        return $alpa->map(function (Collection $baris) use ($hariEfektif, $minimal): ?object {
            $tanggalAlpa = $baris
                ->map(fn (object $b) => Carbon::parse($b->tanggal)->toDateString())
                ->flip();

            $beruntunTerpanjang = 0;
            $berjalan = 0;

            foreach ($hariEfektif as $tanggal) {
                $berjalan = $tanggalAlpa->has($tanggal) ? $berjalan + 1 : 0;
                $beruntunTerpanjang = max($beruntunTerpanjang, $berjalan);
            }

            if ($beruntunTerpanjang < $minimal) {
                return null;
            }

            $pertama = $baris->first();

            return (object) [
                'santri_id' => $pertama->santri_id,
                'nama_lengkap' => $pertama->nama_lengkap,
                'nama_kelas' => $pertama->nama_kelas,
                'beruntun' => $beruntunTerpanjang,
                'total_alpa' => $baris->count(),
            ];
        })
            ->filter()
            ->sortByDesc('beruntun')
            ->values();
    }

    /** @return list<StatusKehadiran> */
    private function statusHadirEfektif(): array
    {
        // Definisinya hidup di enum, bukan di sini — supaya rekap, ekspor, dan PDF
        // tidak bisa diam-diam punya versi "% kehadiran" masing-masing.
        return array_values(array_filter(
            StatusKehadiran::cases(),
            fn (StatusKehadiran $status) => $status->hadirEfektif(),
        ));
    }

    public function kolomStatus(StatusKehadiran $status): string
    {
        return 'jml_'.strtolower($status->value);
    }
}
