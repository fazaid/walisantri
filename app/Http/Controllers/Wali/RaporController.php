<?php

namespace App\Http\Controllers\Wali;

use App\Http\Controllers\Controller;
use App\Models\KesantrianKarakterRapor;
use App\Models\NilaiAkademik;
use App\Models\TahfidzUjian;
use App\Services\TahunAjaranOptions;
use Illuminate\Support\Collection;

class RaporController extends Controller
{
    public function index()
    {
        $wali = auth()->user();

        $anakList = $wali->anakSantri()->where('status_aktif', true)->get();

        // santri_id dari query string tidak boleh dipercaya: global scope Multitenantable
        // hanya menyaring pesantren_id, jadi tanpa pengecekan ini wali bisa membaca rapor
        // santri keluarga lain di pesantren yang sama. Kalau tidak cocok, jatuhkan ke anak
        // pertama — wali bisa saja menyimpan tautan lama untuk anak yang sudah non-aktif.
        $santriId = (int) request('santri_id');

        if (! $anakList->contains('id', $santriId)) {
            $santriId = $anakList->first()?->id;
        }

        $tahunAjaran = request('tahun_ajaran', TahunAjaranOptions::current());
        $periode = (string) request('periode', '');
        $bulan = $periode === 'Bulanan' ? (string) request('bulan', '') : null;

        if (! $santriId) {
            return view('wali.rapor', [
                'anakList' => $anakList,
                'santriId' => null,
                'tahunAjaran' => $tahunAjaran,
                'tahunList' => collect([$tahunAjaran]),
                'periode' => $periode,
                'bulan' => $bulan,
                'raporTahfidz' => collect(),
                'daftarKarakter' => collect(),
                'raporAkademik' => collect(),
            ]);
        }

        // Filter periode kosong = "Semua Periode": kompatibel dengan tautan lama
        // yang belum membawa query param ini.
        $saringPeriode = fn ($query) => $query
            ->when($periode !== '', fn ($q) => $q->where('periode', $periode))
            ->when($periode === 'Bulanan' && $bulan, fn ($q) => $q->where('bulan', $bulan));

        $raporTahfidz = $saringPeriode(TahfidzUjian::where('santri_id', $santriId)
            ->where('tahun_ajaran', $tahunAjaran))
            ->orderBy('periode')
            ->get();

        // Karakter dipegang per (tahun_ajaran, periode, bulan) sejak v4.9 — bukan lagi
        // ditebak dari tanggal_input.
        $daftarKarakter = $saringPeriode(KesantrianKarakterRapor::where('santri_id', $santriId)
            ->where('tahun_ajaran', $tahunAjaran))
            ->orderByDesc('tanggal_input')
            ->get();

        $raporAkademik = $saringPeriode(NilaiAkademik::with('mataPelajaran')
            ->where('santri_id', $santriId)
            ->where('tahun_ajaran', $tahunAjaran))
            ->get()
            ->groupBy('periode');

        $tahunList = $this->tahunAjaranTersedia($santriId, $tahunAjaran);

        return view('wali.rapor', compact(
            'anakList',
            'santriId',
            'tahunAjaran',
            'tahunList',
            'periode',
            'bulan',
            'raporTahfidz',
            'daftarKarakter',
            'raporAkademik',
        ));
    }

    /**
     * Tahun ajaran yang punya data rapor untuk santri ini, digabung dari tiga sumber.
     * Tahun ajaran yang sedang dipilih selalu disertakan supaya opsi terpilih di
     * dropdown tidak pernah kosong.
     */
    private function tahunAjaranTersedia(int $santriId, string $tahunAjaran): Collection
    {
        $dari = fn (string $model) => $model::where('santri_id', $santriId)
            ->distinct()
            ->pluck('tahun_ajaran');

        return collect([$tahunAjaran, TahunAjaranOptions::current()])
            ->merge($dari(TahfidzUjian::class))
            ->merge($dari(NilaiAkademik::class))
            ->merge($dari(KesantrianKarakterRapor::class))
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();
    }
}
