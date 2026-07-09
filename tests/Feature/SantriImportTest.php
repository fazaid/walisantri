<?php

namespace Tests\Feature;

use App\Imports\SantriImport;
use App\Models\Kamar;
use App\Models\Kelas;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Shared\Date;
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
        $import = new SantriImport($pesantren->id);

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
        $import = new SantriImport($pesantren->id);

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
        $lama = Santri::factory()->create(['pesantren_id' => $pesantren->id, 'nis' => '2024004']);
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
        $import = new SantriImport($pesantren->id);

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
        $import = new SantriImport($pesantren->id);

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
        $import = new SantriImport($pesantren->id);

        $serial = Date::dateTimeToExcel(new \DateTime('2011-07-20'));

        $import->collection(new Collection([
            ['nis' => '2024009', 'nama_lengkap' => 'Dari Serial Excel', 'tanggal_lahir' => $serial],
        ]));

        $this->assertSame(1, $import->imported);
        $this->assertSame('2011-07-20', Santri::where('nis', '2024009')->first()->tanggal_lahir->format('Y-m-d'));
    }

    public function test_import_resolve_kelas_dan_kamar_berdasarkan_nama(): void
    {
        $pesantren = $this->makePesantren();
        $kelas = Kelas::factory()->create(['pesantren_id' => $pesantren->id, 'nama_kelas' => 'Tahfidz 1']);
        $kamar = Kamar::create(['pesantren_id' => $pesantren->id, 'nama_kamar' => 'Kamar A', 'kapasitas' => 10]);

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
        $kelas = Kelas::factory()->create(['pesantren_id' => $pesantren->id, 'nama_kelas' => 'Tahfidz 1']);
        $kamar = Kamar::create(['pesantren_id' => $pesantren->id, 'nama_kamar' => 'Kamar Mawar', 'kapasitas' => 10]);

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
        $import = new SantriImport($pesantren->id);

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
        $import = new SantriImport($pesantren->id);

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
        $import = new SantriImport($pesantren->id);

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
        $import = new SantriImport($pesantren->id);

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

    public function test_analyze_menghitung_ringkasan_tanpa_menyimpan_apa_pun(): void
    {
        $pesantren = $this->makePesantren(kuota: 3);
        Santri::factory()->create(['pesantren_id' => $pesantren->id, 'status_aktif' => true]);

        $lama = Santri::factory()->create(['pesantren_id' => $pesantren->id, 'nis' => '2024030']);
        $lama->delete();

        $import = new SantriImport($pesantren->id);
        $ringkasan = $import->analyze(new Collection([
            ['nis' => '2024027', 'nama_lengkap' => 'Akan Sukses 1'],
            ['nis' => '2024030', 'nama_lengkap' => 'Pakai NIS Bekas'],
            ['nis' => '', 'nama_lengkap' => 'Tanpa NIS'],
            ['nis' => '2024028', 'nama_lengkap' => 'Akan Sukses 2'],
            ['nis' => '2024029', 'nama_lengkap' => 'Kelebihan Kuota'],
        ]));

        $this->assertSame([
            'total' => 5,
            'akan_diimpor' => 2,
            'duplikat' => 1,
            'data_wajib_kosong' => 1,
            'melebihi_kuota' => 1,
            'wali_baru' => 0,
        ], $ringkasan);

        // analyze() tidak boleh menulis apa pun ke database.
        $this->assertSame(1, Santri::where('pesantren_id', $pesantren->id)->count());
        $this->assertSame(0, $import->imported);
    }

    public function test_analyze_santri_non_aktif_tidak_dihitung_ke_kuota(): void
    {
        $pesantren = $this->makePesantren(kuota: 1);
        Santri::factory()->create(['pesantren_id' => $pesantren->id, 'status_aktif' => true]);

        $ringkasan = (new SantriImport($pesantren->id))->analyze(new Collection([
            ['nis' => '2024031', 'nama_lengkap' => 'Alumni 1', 'status' => 'Non-Aktif'],
            ['nis' => '2024032', 'nama_lengkap' => 'Alumni 2', 'status' => 'Non-Aktif'],
        ]));

        $this->assertSame(2, $ringkasan['akan_diimpor']);
        $this->assertSame(0, $ringkasan['melebihi_kuota']);
    }

    public function test_analyze_hasil_konsisten_dengan_hasil_import_sungguhan(): void
    {
        $pesantren = $this->makePesantren(kuota: 2);

        $rows = new Collection([
            ['nis' => '2024033', 'nama_lengkap' => 'A'],
            ['nis' => '2024034', 'nama_lengkap' => 'B'],
            ['nis' => '2024035', 'nama_lengkap' => 'C'],
        ]);

        $ringkasan = (new SantriImport($pesantren->id))->analyze($rows);

        $real = new SantriImport($pesantren->id);
        $real->collection($rows);

        $this->assertSame($ringkasan['akan_diimpor'], $real->imported);
        $this->assertSame(
            $ringkasan['duplikat'] + $ringkasan['data_wajib_kosong'] + $ringkasan['melebihi_kuota'],
            $real->skipped
        );
    }

    public function test_import_wali_baru_dibuat_dan_ditautkan_saat_email_baru(): void
    {
        $pesantren = $this->makePesantren();
        $import = new SantriImport($pesantren->id);

        $import->collection(new Collection([
            ['nis' => '3001', 'nama_lengkap' => 'Anak A', 'wali_nama' => 'Bapak Anu', 'wali_email' => 'bapak.anu@example.com', 'wali_no_hp' => '081211112222'],
        ]));

        $this->assertSame(1, $import->imported);
        $this->assertSame([], $import->errors);

        $santri = Santri::where('nis', '3001')->first();
        $this->assertNotNull($santri->wali_santri_id);

        $wali = User::find($santri->wali_santri_id);
        $this->assertSame('Bapak Anu', $wali->name);
        $this->assertSame('bapak.anu@example.com', $wali->email);
        $this->assertSame('081211112222', $wali->phone_number);
        $this->assertSame('wali_santri', $wali->role);
        $this->assertSame($pesantren->id, $wali->pesantren_id);
    }

    public function test_import_wali_kosong_semua_kolom_tidak_diproses(): void
    {
        $pesantren = $this->makePesantren();
        $import = new SantriImport($pesantren->id);

        $import->collection(new Collection([
            ['nis' => '3002', 'nama_lengkap' => 'Tanpa Wali'],
        ]));

        $this->assertSame([], $import->errors);
        $this->assertNull(Santri::where('nis', '3002')->first()->wali_santri_id);
    }

    public function test_import_wali_email_dan_no_hp_kosong_tapi_kolom_wali_lain_diisi_menghasilkan_warning(): void
    {
        $pesantren = $this->makePesantren();
        $import = new SantriImport($pesantren->id);

        $import->collection(new Collection([
            ['nis' => '3003', 'nama_lengkap' => 'Wali Tanpa Email', 'wali_nama' => 'Ibu Fulan'],
        ]));

        $this->assertSame(1, $import->imported);
        $this->assertNull(Santri::where('nis', '3003')->first()->wali_santri_id);
        $this->assertCount(1, $import->errors);
        $this->assertStringContainsString('wali_email dan wali_no_hp kosong', $import->errors[0]);
    }

    public function test_import_wali_baru_dibuat_dan_ditautkan_saat_hanya_no_hp_diisi(): void
    {
        $pesantren = $this->makePesantren();
        $import = new SantriImport($pesantren->id);

        $import->collection(new Collection([
            ['nis' => '3030', 'nama_lengkap' => 'Anak WA', 'wali_nama' => 'Bapak WA', 'wali_no_hp' => '081211112223'],
        ]));

        $this->assertSame(1, $import->imported);
        $this->assertSame([], $import->errors);

        $santri = Santri::where('nis', '3030')->first();
        $this->assertNotNull($santri->wali_santri_id);

        $wali = User::find($santri->wali_santri_id);
        $this->assertSame('Bapak WA', $wali->name);
        $this->assertNull($wali->email);
        $this->assertSame('6281211112223', $wali->phone_number);
        $this->assertSame('wali_santri', $wali->role);
        $this->assertSame($pesantren->id, $wali->pesantren_id);
    }

    public function test_import_wali_no_hp_format_beda_dianggap_sama_untuk_kakak_adik(): void
    {
        $pesantren = $this->makePesantren();
        $import = new SantriImport($pesantren->id);

        $import->collection(new Collection([
            ['nis' => '3031', 'nama_lengkap' => 'Kakak WA', 'wali_no_hp' => '081234567890'],
            ['nis' => '3032', 'nama_lengkap' => 'Adik WA', 'wali_no_hp' => '6281234567890'],
        ]));

        $this->assertSame([], $import->errors);

        $kakak = Santri::where('nis', '3031')->first();
        $adik = Santri::where('nis', '3032')->first();

        $this->assertNotNull($kakak->wali_santri_id);
        $this->assertSame($kakak->wali_santri_id, $adik->wali_santri_id);
    }

    public function test_import_wali_no_hp_conflict_role_lain_di_pesantren_sama(): void
    {
        $pesantren = $this->makePesantren();
        $ustadz = User::factory()->ustadz()->create([
            'pesantren_id' => $pesantren->id,
            'phone_number' => '6281299998888',
        ]);

        $import = new SantriImport($pesantren->id);
        $import->collection(new Collection([
            ['nis' => '3033', 'nama_lengkap' => 'Conflict Role WA', 'wali_no_hp' => '081299998888'],
        ]));

        $this->assertNull(Santri::where('nis', '3033')->first()->wali_santri_id);
        $this->assertCount(1, $import->errors);
        $this->assertStringContainsString('peran lain', $import->errors[0]);
        $this->assertSame('ustadz', $ustadz->fresh()->role);
    }

    public function test_import_wali_email_format_tidak_valid_menghasilkan_warning(): void
    {
        $pesantren = $this->makePesantren();
        $import = new SantriImport($pesantren->id);

        $import->collection(new Collection([
            ['nis' => '3004', 'nama_lengkap' => 'Email Salah', 'wali_email' => 'bukan-email'],
        ]));

        $this->assertSame(1, $import->imported);
        $this->assertNull(Santri::where('nis', '3004')->first()->wali_santri_id);
        $this->assertCount(1, $import->errors);
        $this->assertStringContainsString('tidak valid', $import->errors[0]);
    }

    public function test_import_wali_dua_baris_email_sama_ditautkan_ke_user_yang_sama(): void
    {
        $pesantren = $this->makePesantren();
        $import = new SantriImport($pesantren->id);

        $import->collection(new Collection([
            ['nis' => '3005', 'nama_lengkap' => 'Kakak', 'wali_nama' => 'Bapak Kel', 'wali_email' => 'satu-keluarga@example.com'],
            ['nis' => '3006', 'nama_lengkap' => 'Adik', 'wali_email' => 'satu-keluarga@example.com'],
        ]));

        $kakak = Santri::where('nis', '3005')->first();
        $adik = Santri::where('nis', '3006')->first();

        $this->assertNotNull($kakak->wali_santri_id);
        $this->assertSame($kakak->wali_santri_id, $adik->wali_santri_id);
        $this->assertSame(1, User::where('email', 'satu-keluarga@example.com')->count());
    }

    public function test_import_wali_email_case_insensitive_dianggap_sama(): void
    {
        $pesantren = $this->makePesantren();
        $import = new SantriImport($pesantren->id);

        $import->collection(new Collection([
            ['nis' => '3007', 'nama_lengkap' => 'A', 'wali_email' => 'Case@Example.com'],
            ['nis' => '3008', 'nama_lengkap' => 'B', 'wali_email' => 'case@example.com'],
        ]));

        $a = Santri::where('nis', '3007')->first();
        $b = Santri::where('nis', '3008')->first();

        $this->assertSame($a->wali_santri_id, $b->wali_santri_id);
        $this->assertSame(1, User::whereRaw('LOWER(email) = ?', ['case@example.com'])->count());
    }

    public function test_import_wali_email_reuse_existing_wali_di_pesantren_sama(): void
    {
        $pesantren = $this->makePesantren();
        $existing = User::factory()->waliSantri()->create([
            'pesantren_id' => $pesantren->id,
            'email' => 'sudah-ada@example.com',
            'name' => 'Wali Lama',
        ]);

        $import = new SantriImport($pesantren->id);
        $import->collection(new Collection([
            ['nis' => '3009', 'nama_lengkap' => 'Reuse', 'wali_nama' => 'Nama Beda', 'wali_email' => 'sudah-ada@example.com'],
        ]));

        $santri = Santri::where('nis', '3009')->first();
        $this->assertSame($existing->id, $santri->wali_santri_id);
        $this->assertSame(1, User::where('email', 'sudah-ada@example.com')->count());

        // Tidak overwrite nama wali yang sudah ada (link-only).
        $this->assertSame('Wali Lama', $existing->fresh()->name);
    }

    public function test_import_wali_email_conflict_role_lain_di_pesantren_sama(): void
    {
        $pesantren = $this->makePesantren();
        $ustadz = User::factory()->ustadz()->create([
            'pesantren_id' => $pesantren->id,
            'email' => 'ustadz@example.com',
        ]);

        $import = new SantriImport($pesantren->id);
        $import->collection(new Collection([
            ['nis' => '3010', 'nama_lengkap' => 'Conflict Role', 'wali_email' => 'ustadz@example.com'],
        ]));

        $this->assertNull(Santri::where('nis', '3010')->first()->wali_santri_id);
        $this->assertCount(1, $import->errors);
        $this->assertStringContainsString('peran lain', $import->errors[0]);
        $this->assertSame('ustadz', $ustadz->fresh()->role);
    }

    public function test_import_wali_email_conflict_pesantren_lain(): void
    {
        $pesantrenA = $this->makePesantren();
        $pesantrenB = $this->makePesantren();
        $waliB = User::factory()->waliSantri()->create([
            'pesantren_id' => $pesantrenB->id,
            'email' => 'lintas-tenant@example.com',
        ]);

        $import = new SantriImport($pesantrenA->id);
        $import->collection(new Collection([
            ['nis' => '3011', 'nama_lengkap' => 'Conflict Tenant', 'wali_email' => 'lintas-tenant@example.com'],
        ]));

        $this->assertNull(Santri::where('nis', '3011')->first()->wali_santri_id);
        $this->assertCount(1, $import->errors);
        $this->assertStringContainsString('pesantren lain', $import->errors[0]);
        $this->assertSame($pesantrenB->id, $waliB->fresh()->pesantren_id);
    }

    public function test_import_wali_nama_kosong_fallback_ke_email(): void
    {
        $pesantren = $this->makePesantren();
        $import = new SantriImport($pesantren->id);

        $import->collection(new Collection([
            ['nis' => '3012', 'nama_lengkap' => 'Tanpa Nama Wali', 'wali_email' => 'noname@example.com'],
        ]));

        $santri = Santri::where('nis', '3012')->first();
        $wali = User::find($santri->wali_santri_id);
        $this->assertSame('noname@example.com', $wali->name);
    }

    public function test_import_wali_password_diacak_dan_di_hash(): void
    {
        $pesantren = $this->makePesantren();
        $import = new SantriImport($pesantren->id);

        $import->collection(new Collection([
            ['nis' => '3013', 'nama_lengkap' => 'A', 'wali_email' => 'pw1@example.com'],
            ['nis' => '3014', 'nama_lengkap' => 'B', 'wali_email' => 'pw2@example.com'],
        ]));

        $wali1 = User::where('email', 'pw1@example.com')->first();
        $wali2 = User::where('email', 'pw2@example.com')->first();

        $this->assertTrue(str_starts_with($wali1->password, '$2y$') || str_starts_with($wali1->password, '$argon2'));
        $this->assertNotSame($wali1->password, $wali2->password);
    }

    public function test_analyze_menghitung_wali_baru(): void
    {
        $pesantren = $this->makePesantren();
        $existing = User::factory()->waliSantri()->create([
            'pesantren_id' => $pesantren->id,
            'email' => 'existing@example.com',
        ]);

        $userCountSebelum = User::count();

        $ringkasan = (new SantriImport($pesantren->id))->analyze(new Collection([
            ['nis' => '3016', 'nama_lengkap' => 'Wali Baru 1', 'wali_email' => 'baru1@example.com'],
            ['nis' => '3017', 'nama_lengkap' => 'Wali Baru 1 Lagi', 'wali_email' => 'baru1@example.com'],
            ['nis' => '3018', 'nama_lengkap' => 'Wali Existing', 'wali_email' => 'existing@example.com'],
            ['nis' => '3019', 'nama_lengkap' => 'Tanpa Wali'],
        ]));

        $this->assertSame(1, $ringkasan['wali_baru']);
        $this->assertSame($userCountSebelum, User::count());
    }

    public function test_analyze_wali_baru_konsisten_dengan_hasil_import_sungguhan(): void
    {
        $pesantren = $this->makePesantren();

        $rows = new Collection([
            ['nis' => '3020', 'nama_lengkap' => 'A', 'wali_email' => 'keluarga1@example.com'],
            ['nis' => '3021', 'nama_lengkap' => 'B', 'wali_email' => 'keluarga1@example.com'],
            ['nis' => '3022', 'nama_lengkap' => 'C', 'wali_email' => 'keluarga2@example.com'],
        ]);

        $ringkasan = (new SantriImport($pesantren->id))->analyze($rows);

        $real = new SantriImport($pesantren->id);
        $real->collection($rows);

        $waliBaruSungguhan = User::where('pesantren_id', $pesantren->id)->where('role', 'wali_santri')->count();

        $this->assertSame($ringkasan['wali_baru'], $waliBaruSungguhan);
    }
}
