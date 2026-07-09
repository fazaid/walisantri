<?php

namespace Tests\Feature;

use App\Imports\SantriImport;
use App\Models\Kamar;
use App\Models\Kelas;
use App\Models\Pesantren;
use App\Models\Santri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class SantriImportTest extends TestCase
{
    use RefreshDatabase;

    private function makePesantren(int $kuota = 10): Pesantren
    {
        return Pesantren::factory()->create(['max_santri_kuota' => $kuota]);
    }

    public function test_import_baris_valid_membuat_santri_baru(): void
    {
        $pesantren = $this->makePesantren();
        $import    = new SantriImport($pesantren->id);

        $import->collection(new Collection([
            ['nis' => '2024001', 'nama_lengkap' => 'Ahmad Fauzi'],
        ]));

        $this->assertSame(1, $import->imported);
        $this->assertSame(0, $import->skipped);
        $this->assertSame([], $import->errors);

        $santri = Santri::where('pesantren_id', $pesantren->id)->where('nis', '2024001')->first();
        $this->assertNotNull($santri);
        $this->assertSame('Ahmad Fauzi', $santri->nama_lengkap);
        $this->assertTrue((bool) $santri->status_aktif);
    }

    public function test_import_baris_tanpa_nis_atau_nama_dilewati(): void
    {
        $pesantren = $this->makePesantren();
        $import    = new SantriImport($pesantren->id);

        $import->collection(new Collection([
            ['nis' => '', 'nama_lengkap' => 'Tanpa NIS'],
            ['nis' => '2024002', 'nama_lengkap' => ''],
        ]));

        $this->assertSame(0, $import->imported);
        $this->assertSame(2, $import->skipped);
        $this->assertCount(2, $import->errors);
        $this->assertSame(0, Santri::where('pesantren_id', $pesantren->id)->count());
    }

    public function test_import_nis_yang_sudah_terdaftar_dilewati(): void
    {
        $pesantren = $this->makePesantren();
        Santri::factory()->create(['pesantren_id' => $pesantren->id, 'nis' => '2024003']);

        $import = new SantriImport($pesantren->id);
        $import->collection(new Collection([
            ['nis' => '2024003', 'nama_lengkap' => 'Duplikat NIS'],
        ]));

        $this->assertSame(0, $import->imported);
        $this->assertSame(1, $import->skipped);
        $this->assertSame(1, Santri::where('pesantren_id', $pesantren->id)->where('nis', '2024003')->count());
    }

    public function test_import_nis_bekas_santri_yang_dihapus_dilewati(): void
    {
        $pesantren = $this->makePesantren();
        $lama      = Santri::factory()->create(['pesantren_id' => $pesantren->id, 'nis' => '2024004']);
        $lama->delete();

        $import = new SantriImport($pesantren->id);
        $import->collection(new Collection([
            ['nis' => '2024004', 'nama_lengkap' => 'Pakai NIS Bekas'],
        ]));

        $this->assertSame(0, $import->imported);
        $this->assertSame(1, $import->skipped);
        $this->assertStringContainsString('sudah pernah terdaftar', $import->errors[0]);
    }

    public function test_import_nis_sama_di_pesantren_lain_tetap_berhasil(): void
    {
        $pesantrenA = $this->makePesantren();
        $pesantrenB = $this->makePesantren();
        Santri::factory()->create(['pesantren_id' => $pesantrenA->id, 'nis' => '2024005']);

        $import = new SantriImport($pesantrenB->id);
        $import->collection(new Collection([
            ['nis' => '2024005', 'nama_lengkap' => 'Santri Pesantren B'],
        ]));

        $this->assertSame(1, $import->imported);
        $this->assertSame(1, Santri::where('pesantren_id', $pesantrenB->id)->where('nis', '2024005')->count());
    }

    public function test_import_parse_tanggal_lahir_format_dd_mm_yyyy_dengan_benar(): void
    {
        $pesantren = $this->makePesantren();
        $import    = new SantriImport($pesantren->id);

        $import->collection(new Collection([
            // Hari & bulan sama-sama <= 12 — kasus yang dulu tertukar jadi M/D/Y.
            ['nis' => '2024006', 'nama_lengkap' => 'Budi', 'tanggal_lahir' => '05/03/2010'],
            // Contoh persis dari template resmi — dulu gagal di-parse total.
            ['nis' => '2024007', 'nama_lengkap' => 'Sari', 'tanggal_lahir' => '15/03/2012'],
        ]));

        $this->assertSame(2, $import->imported);
        $this->assertSame([], $import->errors);

        $this->assertSame('2010-03-05', Santri::where('nis', '2024006')->first()->tanggal_lahir->format('Y-m-d'));
        $this->assertSame('2012-03-15', Santri::where('nis', '2024007')->first()->tanggal_lahir->format('Y-m-d'));
    }

    public function test_import_tanggal_lahir_format_tidak_valid_kolom_diabaikan(): void
    {
        $pesantren = $this->makePesantren();
        $import    = new SantriImport($pesantren->id);

        $import->collection(new Collection([
            ['nis' => '2024008', 'nama_lengkap' => 'Tanggal Rusak', 'tanggal_lahir' => 'bukan-tanggal'],
        ]));

        $this->assertSame(1, $import->imported);
        $this->assertCount(1, $import->errors);
        $this->assertStringContainsString('Format tanggal lahir', $import->errors[0]);

        $santri = Santri::where('nis', '2024008')->first();
        $this->assertNull($santri->tanggal_lahir);
    }

    public function test_import_tanggal_lahir_dari_serial_excel_numerik(): void
    {
        $pesantren = $this->makePesantren();
        $import    = new SantriImport($pesantren->id);

        $serial = \PhpOffice\PhpSpreadsheet\Shared\Date::dateTimeToExcel(new \DateTime('2011-07-20'));

        $import->collection(new Collection([
            ['nis' => '2024009', 'nama_lengkap' => 'Dari Serial Excel', 'tanggal_lahir' => $serial],
        ]));

        $this->assertSame(1, $import->imported);
        $this->assertSame('2011-07-20', Santri::where('nis', '2024009')->first()->tanggal_lahir->format('Y-m-d'));
    }

    public function test_import_resolve_kelas_dan_kamar_berdasarkan_nama(): void
    {
        $pesantren = $this->makePesantren();
        $kelas     = Kelas::factory()->create(['pesantren_id' => $pesantren->id, 'nama_kelas' => 'Tahfidz 1']);
        $kamar     = Kamar::create(['pesantren_id' => $pesantren->id, 'nama_kamar' => 'Kamar A', 'kapasitas' => 10]);

        $import = new SantriImport($pesantren->id);
        $import->collection(new Collection([
            ['nis' => '2024010', 'nama_lengkap' => 'Ada Kelas Kamar', 'kelas' => 'Tahfidz 1', 'kamar' => 'Kamar A'],
        ]));

        $santri = Santri::where('nis', '2024010')->first();
        $this->assertSame($kelas->id, $santri->kelas_id);
        $this->assertSame($kamar->id, $santri->kamar_id);
        $this->assertSame([], $import->errors);
    }

    public function test_import_resolve_kelas_dan_kamar_tidak_case_sensitive(): void
    {
        $pesantren = $this->makePesantren();
        $kelas     = Kelas::factory()->create(['pesantren_id' => $pesantren->id, 'nama_kelas' => 'Tahfidz 1']);
        $kamar     = Kamar::create(['pesantren_id' => $pesantren->id, 'nama_kamar' => 'Kamar Mawar', 'kapasitas' => 10]);

        $import = new SantriImport($pesantren->id);
        $import->collection(new Collection([
            ['nis' => '2024020', 'nama_lengkap' => 'Huruf Kecil', 'kelas' => 'tahfidz 1', 'kamar' => 'kamar mawar'],
            ['nis' => '2024021', 'nama_lengkap' => 'Huruf Besar', 'kelas' => 'TAHFIDZ 1', 'kamar' => 'KAMAR MAWAR'],
        ]));

        $this->assertSame(2, $import->imported);
        $this->assertSame([], $import->errors);

        $this->assertSame($kelas->id, Santri::where('nis', '2024020')->first()->kelas_id);
        $this->assertSame($kamar->id, Santri::where('nis', '2024020')->first()->kamar_id);
        $this->assertSame($kelas->id, Santri::where('nis', '2024021')->first()->kelas_id);
        $this->assertSame($kamar->id, Santri::where('nis', '2024021')->first()->kamar_id);
    }

    public function test_import_kelas_atau_kamar_tidak_ditemukan_menghasilkan_warning(): void
    {
        $pesantren = $this->makePesantren();

        $import = new SantriImport($pesantren->id);
        $import->collection(new Collection([
            ['nis' => '2024011', 'nama_lengkap' => 'Kelas Salah Ketik', 'kelas' => 'Kelas Tidak Ada', 'kamar' => 'Kamar Tidak Ada'],
        ]));

        $santri = Santri::where('nis', '2024011')->first();
        $this->assertSame(1, $import->imported);
        $this->assertNull($santri->kelas_id);
        $this->assertNull($santri->kamar_id);
        $this->assertCount(2, $import->errors);
    }

    public function test_import_resolve_jenis_kelamin_berbagai_format_teks(): void
    {
        $pesantren = $this->makePesantren();
        $import    = new SantriImport($pesantren->id);

        $import->collection(new Collection([
            ['nis' => '2024012', 'nama_lengkap' => 'A', 'jenis_kelamin' => 'L'],
            ['nis' => '2024013', 'nama_lengkap' => 'B', 'jenis_kelamin' => 'Laki-laki'],
            ['nis' => '2024014', 'nama_lengkap' => 'C', 'jenis_kelamin' => 'P'],
            ['nis' => '2024015', 'nama_lengkap' => 'D', 'jenis_kelamin' => 'Perempuan'],
        ]));

        $this->assertSame(4, $import->imported);
        $this->assertSame('laki_laki', Santri::where('nis', '2024012')->first()->jenis_kelamin->value);
        $this->assertSame('laki_laki', Santri::where('nis', '2024013')->first()->jenis_kelamin->value);
        $this->assertSame('perempuan', Santri::where('nis', '2024014')->first()->jenis_kelamin->value);
        $this->assertSame('perempuan', Santri::where('nis', '2024015')->first()->jenis_kelamin->value);
    }

    public function test_import_jenis_kelamin_tidak_dikenali_menghasilkan_warning(): void
    {
        $pesantren = $this->makePesantren();
        $import    = new SantriImport($pesantren->id);

        $import->collection(new Collection([
            ['nis' => '2024016', 'nama_lengkap' => 'Gender Aneh', 'jenis_kelamin' => 'tidak diketahui'],
        ]));

        $santri = Santri::where('nis', '2024016')->first();
        $this->assertSame(1, $import->imported);
        $this->assertNull($santri->jenis_kelamin);
        $this->assertCount(1, $import->errors);
    }

    public function test_import_resolve_status_aktif_berbagai_format_teks(): void
    {
        $pesantren = $this->makePesantren();
        $import    = new SantriImport($pesantren->id);

        $import->collection(new Collection([
            ['nis' => '2024022', 'nama_lengkap' => 'Aktif Eksplisit', 'status' => 'Aktif'],
            ['nis' => '2024023', 'nama_lengkap' => 'Non Aktif', 'status' => 'Non-Aktif'],
            ['nis' => '2024024', 'nama_lengkap' => 'Kolom Kosong'],
        ]));

        $this->assertSame(3, $import->imported);
        $this->assertSame([], $import->errors);

        $this->assertTrue((bool) Santri::where('nis', '2024022')->first()->status_aktif);
        $this->assertFalse((bool) Santri::where('nis', '2024023')->first()->status_aktif);
        $this->assertTrue((bool) Santri::where('nis', '2024024')->first()->status_aktif);
    }

    public function test_import_status_tidak_dikenali_dianggap_aktif_dengan_warning(): void
    {
        $pesantren = $this->makePesantren();
        $import    = new SantriImport($pesantren->id);

        $import->collection(new Collection([
            ['nis' => '2024025', 'nama_lengkap' => 'Status Aneh', 'status' => 'entahlah'],
        ]));

        $santri = Santri::where('nis', '2024025')->first();
        $this->assertSame(1, $import->imported);
        $this->assertTrue((bool) $santri->status_aktif);
        $this->assertCount(1, $import->errors);
        $this->assertStringContainsString('dianggap Aktif', $import->errors[0]);
    }

    public function test_import_santri_non_aktif_tidak_kena_batas_kuota(): void
    {
        $pesantren = $this->makePesantren(kuota: 1);
        Santri::factory()->create(['pesantren_id' => $pesantren->id, 'status_aktif' => true]);

        $import = new SantriImport($pesantren->id);
        $import->collection(new Collection([
            ['nis' => '2024026', 'nama_lengkap' => 'Alumni', 'status' => 'Non-Aktif'],
        ]));

        $this->assertSame(1, $import->imported);
        $this->assertSame([], $import->errors);
        $this->assertFalse((bool) Santri::where('nis', '2024026')->first()->status_aktif);
    }

    public function test_import_berhenti_menambah_setelah_kuota_penuh_tapi_baris_sebelumnya_tetap_tersimpan(): void
    {
        $pesantren = $this->makePesantren(kuota: 2);

        $import = new SantriImport($pesantren->id);
        $import->collection(new Collection([
            ['nis' => '2024017', 'nama_lengkap' => 'Kuota 1'],
            ['nis' => '2024018', 'nama_lengkap' => 'Kuota 2'],
            ['nis' => '2024019', 'nama_lengkap' => 'Melebihi Kuota'],
        ]));

        $this->assertSame(2, $import->imported);
        $this->assertSame(1, $import->skipped);
        $this->assertStringContainsString('kuota', strtolower($import->errors[0]));

        $this->assertSame(2, Santri::where('pesantren_id', $pesantren->id)->count());
        $this->assertNotNull(Santri::where('nis', '2024017')->first());
        $this->assertNotNull(Santri::where('nis', '2024018')->first());
        $this->assertNull(Santri::where('nis', '2024019')->first());
    }
}
