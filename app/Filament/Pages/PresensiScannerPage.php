<?php

namespace App\Filament\Pages;

use App\Enums\StatusKehadiran;
use App\Enums\SumberPresensi;
use App\Enums\UserRole;
use App\Filament\Clusters\Presensi as ClusterPresensi;
use App\Models\Presensi;
use App\Models\PresensiPengaturan;
use App\Models\Santri;
use App\Services\PresensiKalender;
use App\Support\KodePresensi;
use App\Support\PenugasanUstadz;
use App\Support\Waktu;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Absen dengan memindai kartu QR santri.
 *
 * Mekanismenya sengaja berbasis INPUT TEKS ber-autofocus, bukan kamera: alat
 * pemindai (scanner gun) USB/Bluetooth berperilaku sebagai papan ketik — ia
 * mengetikkan kode lalu menekan Enter — sehingga nol dependensi JS, jalan di semua
 * browser, dan bisa diuji penuh lewat Livewire::test. Kolom yang sama menerima
 * ketikan manual saat kartu tertinggal atau QR-nya lecek. Kamera bisa menyusul
 * sebagai peningkatan tanpa mengubah jalur ini.
 */
class PresensiScannerPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static ?string $cluster = ClusterPresensi::class;

    protected static ?string $title = 'Scan Kartu Presensi';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'scan';

    protected string $view = 'filament.pages.presensi-scanner-page';

    public string $kode = '';

    /** @var list<array{nama: string, status: string, waktu: string, nada: string, pesan: string}> */
    public array $riwayat = [];

    public static function canAccess(): bool
    {
        return in_array(Auth::user()?->role, [
            UserRole::AdminPesantren->value,
            UserRole::Ustadz->value,
        ], true);
    }

    public function pengaturan(): PresensiPengaturan
    {
        return PresensiPengaturan::untuk(Auth::user()->pesantren_id);
    }

    public function keteranganLibur(): ?string
    {
        return PresensiKalender::untuk(Auth::user()->pesantren_id)
            ->keteranganLibur(Waktu::hariIni());
    }

    /**
     * @param  string|null  $dariKamera  kode hasil pindaian kamera.
     *
     * Jalur kamera mengirim kodenya sebagai ARGUMEN, bukan lewat `$wire.set()`
     * lalu `$wire.call()`. Dua pemanggilan itu berarti dua round-trip dan dua
     * render ulang untuk satu kartu — dan tiap render ulang adalah satu
     * kesempatan bagi morph Livewire mengusik DOM kamera. Jalur ketik manual
     * tetap memakai `$this->kode` lewat wire:model.
     */
    public function scan(?string $dariKamera = null): void
    {
        $masukan = trim($dariKamera ?? $this->kode);
        $this->kode = '';

        if ($masukan === '') {
            return;
        }

        // Beberapa payload menempel jadi satu string. Penyebab lazimnya alat
        // pemindai yang tidak dikonfigurasi mengirim Enter setelah kode, sehingga
        // pindaian kedua dan seterusnya menumpuk di kolom yang sama. "Tidak
        // dikenali" benar secara harfiah tapi tidak menolong sama sekali —
        // petugas tidak akan menduga masalahnya ada di setelan alatnya.
        if (substr_count($masukan, KodePresensi::PREFIKS) > 1) {
            $this->catatRiwayat(
                'Beberapa kode sekaligus',
                'danger',
                'Kolom berisi lebih dari satu kode. Alat pemindai Anda kemungkinan belum diatur mengirim Enter setelah memindai — kosongkan kolomnya, lalu pindai satu kartu saja.',
            );

            return;
        }

        $santri = $this->cariSantri($masukan);

        if (! $santri) {
            $this->catatRiwayat('Tidak dikenali', 'danger', 'Kode/NIS tidak ditemukan di pesantren ini.', $masukan);

            return;
        }

        if (! $santri->status_aktif) {
            $this->catatRiwayat($santri->nama_lengkap, 'danger', 'Santri berstatus non-aktif.');

            return;
        }

        if (! $this->dalamCakupan($santri)) {
            $this->catatRiwayat($santri->nama_lengkap, 'danger', 'Santri ini di luar kelas perwalian Anda.');

            return;
        }

        $this->catat($santri);
    }

    /**
     * Kode kartu dulu, baru NIS.
     *
     * NIS diterima sebagai jalan keluar saat kartu tertinggal — tapi ia BUKAN
     * pengganti kode: NIS berurutan dan tercetak di banyak berkas lain, jadi ia
     * hanya berguna karena petugas yang memasukkannya sudah terautentikasi.
     */
    private function cariSantri(string $masukan): ?Santri
    {
        return KodePresensi::cariSantri($masukan)
            ?? Santri::where('nis', KodePresensi::bacaPayload($masukan))->first();
    }

    private function dalamCakupan(Santri $santri): bool
    {
        if (Auth::user()?->role !== UserRole::Ustadz->value) {
            return true;
        }

        return PenugasanUstadz::kelasIdsPerwalian()->contains($santri->kelas_id);
    }

    private function catat(Santri $santri): void
    {
        $pengaturan = $this->pengaturan();
        $sekarang = Waktu::sekarang();
        $hariIni = Waktu::hariIni();

        $sudahAda = Presensi::where('santri_id', $santri->id)
            ->whereDate('tanggal', $hariIni)
            ->where('jam_ke', Presensi::HARIAN)
            ->first();

        if ($sudahAda) {
            // Pemindaian ganda adalah kejadian NORMAL, bukan error: antrean padat,
            // petugas ragu, kartu tersenggol dua kali. Jam pemindaian PERTAMA
            // dipertahankan — kalau ditimpa, santri yang datang tepat waktu lalu
            // lewat lagi setelah batas akan berubah jadi terlambat.
            $this->catatRiwayat(
                $santri->nama_lengkap,
                'warning',
                'Sudah tercatat '.($sudahAda->dicatat_at?->timezone(Waktu::zona())->format('H:i') ?? '—')
                    .' sebagai '.$sudahAda->status->label().'.',
            );

            return;
        }

        [$status, $menitTerlambat] = $this->tentukanStatus($pengaturan, $sekarang);

        try {
            Presensi::create([
                'pesantren_id' => $santri->pesantren_id,
                'santri_id' => $santri->id,
                'tanggal' => $hariIni,
                'jam_ke' => Presensi::HARIAN,
                'kelas_id' => $santri->kelas_id,
                'status' => $status,
                'menit_terlambat' => $menitTerlambat,
                'sumber' => SumberPresensi::Qr,
                'dicatat_oleh' => Auth::id(),
                'dicatat_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Dua petugas memindai kartu yang sama nyaris bersamaan. Cek di atas
            // adalah SELECT-lalu-INSERT yang tidak atomik, jadi tabrakan ini tetap
            // mungkin — dan ia bukan kesalahan siapa pun.
            $this->catatRiwayat($santri->nama_lengkap, 'warning', 'Sudah tercatat oleh petugas lain.');

            return;
        }

        $this->catatRiwayat(
            $santri->nama_lengkap,
            $status === StatusKehadiran::Terlambat ? 'warning' : 'success',
            $status === StatusKehadiran::Terlambat
                ? 'Terlambat '.$menitTerlambat.' menit.'
                : 'Hadir.',
        );
    }

    /** @return array{0: StatusKehadiran, 1: int|null} */
    private function tentukanStatus(PresensiPengaturan $pengaturan, Carbon $sekarang): array
    {
        $batas = Carbon::parse($sekarang->toDateString().' '.$pengaturan->jam_masuk, Waktu::zona())
            ->addMinutes($pengaturan->toleransi_terlambat_menit);

        if ($sekarang->lte($batas)) {
            return [StatusKehadiran::Hadir, null];
        }

        return [StatusKehadiran::Terlambat, (int) $batas->diffInMinutes($sekarang)];
    }

    private function catatRiwayat(string $nama, string $nada, string $pesan, ?string $masukan = null): void
    {
        array_unshift($this->riwayat, [
            'nama' => $masukan ? $nama.' ('.$masukan.')' : $nama,
            'status' => $pesan,
            'waktu' => Waktu::sekarang()->format('H:i:s'),
            'nada' => $nada,
            'pesan' => $pesan,
        ]);

        // Layar scan dipakai berdiri di depan pintu; riwayat panjang tidak berguna
        // dan hanya memperbesar payload Livewire tiap pemindaian.
        $this->riwayat = array_slice($this->riwayat, 0, 15);
    }
}
