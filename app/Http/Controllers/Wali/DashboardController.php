<?php

namespace App\Http\Controllers\Wali;

use App\Enums\StatusTagihanSpp;
use App\Http\Controllers\Controller;
use App\Models\KesantrianInventaris;
use App\Models\MasterPengumuman;
use App\Models\Presensi;
use App\Models\Santri;
use App\Models\TagihanSpp;
use App\Models\UangSakuSantri;
use App\Services\SantriDetailPresenter;
use App\Support\Waktu;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index()
    {
        $wali = auth()->user();

        $anakList = $wali->anakSantri()
            ->with(['pesantren', 'kelas', 'kamar'])
            ->where('status_aktif', true)
            ->get();

        $santri = null;
        $detail = null;
        $cards = collect();

        if ($anakList->count() === 1) {
            $santri = $anakList->first();
            $detail = SantriDetailPresenter::detail($santri);
            $statusKesehatanList = collect([[
                'santri' => $santri,
                'statusKesehatan' => $detail['statusKesehatanTerkini'],
            ]]);
        } else {
            $summaries = SantriDetailPresenter::cardSummaryMany($anakList);
            $cards = $anakList->map(fn ($s) => array_merge(
                ['santri' => $s],
                $summaries->get($s->id)
            ));
            $statusKesehatanList = $cards;
        }

        // Alert kesehatan lintas anak
        $alertKesehatan = $statusKesehatanList
            ->filter(fn ($c) => in_array($c['statusKesehatan']['status_pemulihan'] ?? null, ['Istirahat_Total', 'Rujukan_Luar']))
            ->map(fn ($c) => [
                'nama' => $c['santri']->nama_lengkap,
                'status' => $c['statusKesehatan']['status_pemulihan'],
                'tanggal_periksa' => $c['statusKesehatan']['tanggal_periksa'],
            ]);

        $pengumuman = MasterPengumuman::where('pesantren_id', $wali->pesantren_id)
            ->forWali()->latest()->limit(5)->get();

        // Broadcast global dari MasterPengumuman (pesantren_id null) — bukan model MasterPengumumanCentral.
        $pengumumanGlobal = MasterPengumuman::withoutGlobalScope('pesantren')
            ->whereNull('pesantren_id')
            ->forWali()->latest()->limit(3)->get();

        $santriIds = $anakList->pluck('id');
        $tunggakanSpp = TagihanSpp::withoutGlobalScope('pesantren')
            ->whereIn('santri_id', $santriIds)
            ->where('status', StatusTagihanSpp::BelumBayar)
            ->count();

        $alertKehadiran = $this->alertKehadiran($anakList, $santriIds);

        $totalInventaris = KesantrianInventaris::whereIn('santri_id', $santriIds)->count();
        $firstSantriId = $anakList->first()?->id;

        // Saldo uang saku agregat lintas anak (setoran − pengambilan).
        $totalSaldoUangSaku = $anakList->sum(fn ($s) => UangSakuSantri::getSaldo($s->id));

        return view('wali.dashboard', compact(
            'wali',
            'santri',
            'detail',
            'cards',
            'alertKesehatan',
            'alertKehadiran',
            'pengumuman',
            'pengumumanGlobal',
            'tunggakanSpp',
            'totalInventaris',
            'totalSaldoUangSaku',
            'firstSantriId',
        ));
    }

    /**
     * Anak yang hari ini tercatat TIDAK hadir.
     *
     * Berangkat dari baris presensi yang benar-benar ADA — hari tanpa catatan
     * tidak pernah dianggap ketidakhadiran. Sistem ini memang tidak pernah menandai
     * Alpa otomatis (§11), dan menebaknya di sini akan mengirim kabar buruk ke
     * orang tua hanya karena ustadznya belum sempat mengisi.
     *
     * Ini satu-satunya kanal pemberitahuan ketidakhadiran, dan itu keputusan sadar
     * (§8, v4.26): integrasi WhatsApp sengaja dimatikan, dan `users.email` wali
     * boleh null karena mereka dirancang passwordless lewat Magic Link.
     *
     * @param  Collection<int, Santri>  $anakList
     * @param  Collection<int, int>  $santriIds
     * @return Collection<int, array{nama: string, status: string, santri_id: int}>
     */
    private function alertKehadiran($anakList, $santriIds)
    {
        if ($santriIds->isEmpty()) {
            return collect();
        }

        $nama = $anakList->pluck('nama_lengkap', 'id');

        return Presensi::whereIn('santri_id', $santriIds)
            ->whereDate('tanggal', Waktu::hariIni())
            ->where('jam_ke', Presensi::HARIAN)
            ->get()
            // Terlambat dan Dispensasi TIDAK memicu banner: keduanya dihitung hadir
            // (StatusKehadiran::hadirEfektif()), dan memakai definisi berbeda di sini
            // berarti wali membaca "tidak hadir" di Beranda lalu "100% hadir" di
            // halaman presensi anak yang sama.
            ->reject(fn (Presensi $baris): bool => $baris->status->hadirEfektif())
            ->map(fn (Presensi $baris): array => [
                'santri_id' => $baris->santri_id,
                'nama' => $nama->get($baris->santri_id, 'Santri'),
                'status' => $baris->status->label(),
            ])
            ->values();
    }
}
