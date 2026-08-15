<?php

namespace Tests\Feature;

use App\Enums\StatusKehadiran;
use App\Exports\PresensiRekapExport;
use App\Models\Kelas;
use App\Models\Pesantren;
use App\Models\Presensi;
use App\Models\PresensiPengaturan;
use App\Models\Santri;
use App\Models\User;
use App\Support\Waktu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class PresensiExportTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Pesantren, 1: User, 2: Kelas} */
    private function siapkan(): array
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);
        $kelas = Kelas::factory()->create(['pesantren_id' => $pesantren->id]);

        PresensiPengaturan::untuk($pesantren->id)->update(['hari_libur_mingguan' => []]);

        return [$pesantren, $admin, $kelas];
    }

    private function parameter(): array
    {
        return [
            'tahun_ajaran' => '2026/2027',
            'periode' => 'Bulanan',
            'bulan' => Waktu::sekarang()->month.'-'.Waktu::sekarang()->year,
        ];
    }

    public function test_admin_bisa_mengunduh_rekap(): void
    {
        [, $admin] = $this->siapkan();
        Excel::fake();

        $this->actingAs($admin)
            ->get(route('admin.export.presensi', $this->parameter()))
            ->assertOk();

        Excel::assertDownloaded('rekap-presensi-2026-2027-bulanan.xlsx');
    }

    public function test_ustadz_boleh_mengunduh(): void
    {
        [$pesantren] = $this->siapkan();
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);
        Excel::fake();

        $this->actingAs($ustadz)
            ->get(route('admin.export.presensi', $this->parameter()))
            ->assertOk();
    }

    public function test_wali_santri_ditolak(): void
    {
        [$pesantren] = $this->siapkan();
        $wali = User::factory()->waliSantri()->create(['pesantren_id' => $pesantren->id]);

        $this->actingAs($wali)
            ->get(route('admin.export.presensi', $this->parameter()))
            ->assertForbidden();
    }

    public function test_isi_ekspor_sama_dengan_hitungan_rekap(): void
    {
        [$pesantren, , $kelas] = $this->siapkan();

        $santri = Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
            'nama_lengkap' => 'Ahmad Fauzi',
        ]);

        Presensi::withoutGlobalScope('pesantren')->create([
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'kelas_id' => $kelas->id,
            'tanggal' => Waktu::hariIni(),
            'jam_ke' => Presensi::HARIAN,
            'status' => StatusKehadiran::Hadir,
        ]);

        $export = new PresensiRekapExport(
            $pesantren->id,
            '2026/2027',
            'Bulanan',
            Waktu::sekarang()->month.'-'.Waktu::sekarang()->year,
        );

        $mentah = $export->collection()->first();
        $baris = $export->map($mentah);
        $judul = $export->headings();

        $this->assertSame('Ahmad Fauzi', $baris[0]);
        $this->assertSame($kelas->nama_kelas, $baris[1]);

        // Jumlah kolom judul dan kolom isi harus sama — kalau enum StatusKehadiran
        // bertambah nilai, keduanya ikut bertambah bersama atau tesnya merah.
        $this->assertCount(count($judul), $baris);

        // Angka di ekspor adalah angka yang dihitung PresensiRekap, bukan hasil
        // query terpisah. Tiga kolom terakhir: Tanpa Keterangan, Hari Efektif, %.
        $this->assertSame($mentah->tanpa_keterangan, $baris[count($baris) - 3]);
        $this->assertSame($mentah->hari_efektif, $baris[count($baris) - 2]);
        $this->assertSame($mentah->persen_kehadiran, $baris[count($baris) - 1]);

        // Satu hari hadir dari rentang yang di-clamp ke hari ini.
        $this->assertSame(1, (int) $mentah->jml_hadir);
    }

    public function test_judul_sheet_menyebut_periodenya(): void
    {
        [$pesantren] = $this->siapkan();

        $export = new PresensiRekapExport($pesantren->id, '2026/2027', 'Semester_Ganjil');

        $this->assertStringContainsString('Semester Ganjil', $export->title());
    }
}
