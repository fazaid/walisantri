<?php

namespace Tests\Feature;

use App\Exports\AntreanDemoExport;
use App\Exports\DataPesantrenExport;
use App\Filament\Resources\DemoRequests\DemoRequestResource;
use App\Filament\Resources\DemoRequests\Pages\ListDemoRequests;
use App\Filament\Resources\Pesantrens\Pages\ListPesantrens;
use App\Filament\Resources\Pesantrens\PesantrenResource;
use App\Models\DemoRequest;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon as SupportCarbon;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

/**
 * Ekspor Excel di permukaan super admin (Antrean Demo & Data Pesantren).
 *
 * Yang paling perlu dijaga di sini bukan "berkasnya terunduh" melainkan
 * "berkasnya berisi baris yang sama dengan yang sedang tampil". Itulah satu
 * alasan kedua ekspor ini tidak lewat rute admin.export.* seperti ekspor
 * lain — dan satu-satunya hal yang membuktikannya adalah tes filter di bawah.
 */
class SuperAdminExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // DemoRequestObserver::created() men-dispatch notifikasi WhatsApp, dan
        // queue di testing bernilai sync — tanpa fake, membuat satu antrean demo
        // berarti benar-benar memanggil gateway.
        Queue::fake();

        // Hari kerja (Rabu) supaya slaCutoff() deterministik.
        $this->travelTo(SupportCarbon::parse('2026-07-22 10:00:00'));
    }

    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create(['pesantren_id' => null]);
    }

    /**
     * created_at bukan bagian dari $fillable, jadi diset manual sebelum save.
     * Pola yang sama dipakai DemoRequestOverdueTest.
     */
    private function demoRequestAt(Carbon $createdAt, ?Carbon $contactedAt = null, array $atribut = []): DemoRequest
    {
        $record = new DemoRequest(array_merge([
            'nama_pesantren' => 'Pesantren Uji',
            'nama_kontak' => 'PIC Uji',
            'email' => 'uji@example.com',
            'no_hp' => '08123456789',
            'contacted_at' => $contactedAt,
        ], $atribut));
        $record->created_at = $createdAt;
        $record->save();

        return $record->refresh();
    }

    /** Satu baris hasil map(), dikunci ke headingnya supaya tes tidak rapuh terhadap urutan kolom. */
    private function baris(object $export, $record): array
    {
        return array_combine($export->headings(), $export->map($record));
    }

    // ---------- Unduhan dasar ----------

    public function test_super_admin_bisa_mengunduh_antrean_demo(): void
    {
        $this->actingAs($this->superAdmin());
        $this->demoRequestAt(now());
        Excel::fake();

        Livewire::test(ListDemoRequests::class)->callAction('export_excel');

        Excel::assertDownloaded('antrean-demo-2026-07-22.xlsx');
    }

    public function test_super_admin_bisa_mengunduh_data_pesantren(): void
    {
        $this->actingAs($this->superAdmin());
        Pesantren::factory()->create();
        Excel::fake();

        Livewire::test(ListPesantrens::class)->callAction('export_excel');

        Excel::assertDownloaded('data-pesantren-2026-07-22.xlsx');
    }

    /**
     * Tanpa Excel::fake() — satu-satunya tes yang benar-benar melewati rantai
     * Filament action → Livewire SupportFileDownloads → BinaryFileResponse →
     * PhpSpreadsheet. Tes lain memakai fake dan karena itu tidak membuktikan
     * berkasnya sungguh terbentuk; kalau rantai ini putus, hanya tes ini yang
     * merah. (Excel::fake() TIDAK boleh digabung dengan assertFileDownloaded:
     * ExcelFake mengembalikan response tanpa header Content-Disposition,
     * sehingga nama berkasnya jatuh jadi 'download'.)
     */
    public function test_ekspor_benar_benar_menghasilkan_berkas_xlsx(): void
    {
        $this->actingAs($this->superAdmin());
        Pesantren::factory()->create();
        $this->demoRequestAt(now());

        Livewire::test(ListPesantrens::class)
            ->callAction('export_excel')
            ->assertFileDownloaded('data-pesantren-2026-07-22.xlsx');

        Livewire::test(ListDemoRequests::class)
            ->callAction('export_excel')
            ->assertFileDownloaded('antrean-demo-2026-07-22.xlsx');
    }

    // ---------- Inti fitur: berkas mengikuti tabel ----------

    public function test_ekspor_pesantren_mengikuti_filter_status_yang_aktif(): void
    {
        $this->actingAs($this->superAdmin());
        $expired = Pesantren::factory()->create(['status_berlangganan' => 'expired']);
        Pesantren::factory()->create(['status_berlangganan' => 'active']);
        Excel::fake();

        Livewire::test(ListPesantrens::class)
            ->filterTable('status_berlangganan', ['expired'])
            ->callAction('export_excel');

        Excel::assertDownloaded(
            'data-pesantren-2026-07-22.xlsx',
            fn (DataPesantrenExport $export) => $export->query()->pluck('id')->all() === [$expired->id],
        );
    }

    public function test_ekspor_pesantren_menghormati_pencarian_yang_aktif(): void
    {
        $this->actingAs($this->superAdmin());
        $dicari = Pesantren::factory()->create(['nama_pesantren' => 'Pesantren Nurul Huda']);
        Pesantren::factory()->create(['nama_pesantren' => 'Pesantren Al Falah']);
        Excel::fake();

        Livewire::test(ListPesantrens::class)
            ->searchTable('Nurul')
            ->callAction('export_excel');

        Excel::assertDownloaded(
            'data-pesantren-2026-07-22.xlsx',
            fn (DataPesantrenExport $export) => $export->query()->pluck('id')->all() === [$dicari->id],
        );
    }

    public function test_ekspor_pesantren_tidak_menyertakan_tenant_demo_secara_bawaan(): void
    {
        $this->actingAs($this->superAdmin());
        $pelanggan = Pesantren::factory()->create(['is_demo' => false]);
        Pesantren::factory()->create(['is_demo' => true]);
        Excel::fake();

        // Tanpa menyentuh filter apa pun: TernaryFilter is_demo punya cabang
        // blank: yang menyembunyikan tenant sandbox, dan itu harus ikut terbawa.
        Livewire::test(ListPesantrens::class)->callAction('export_excel');

        Excel::assertDownloaded(
            'data-pesantren-2026-07-22.xlsx',
            fn (DataPesantrenExport $export) => $export->query()->pluck('id')->all() === [$pelanggan->id],
        );
    }

    public function test_ekspor_antrean_demo_mengikuti_filter_belum_dihubungi(): void
    {
        $this->actingAs($this->superAdmin());
        $belum = $this->demoRequestAt(now());
        $this->demoRequestAt(now(), contactedAt: now());
        Excel::fake();

        Livewire::test(ListDemoRequests::class)
            ->filterTable('contacted', false)
            ->callAction('export_excel');

        Excel::assertDownloaded(
            'antrean-demo-2026-07-22.xlsx',
            fn (AntreanDemoExport $export) => $export->query()->pluck('id')->all() === [$belum->id],
        );
    }

    public function test_urutan_deterministik_menjaga_baris_tidak_hilang_saat_chunk(): void
    {
        $this->actingAs($this->superAdmin());

        // created_at identik: tanpa tiebreaker unik, chunk berbasis offset/limit
        // bisa mengulang atau melewati baris tanpa galat apa pun.
        Pesantren::factory()->count(5)->create(['created_at' => now()]);

        $lewatTabel = (new DataPesantrenExport(
            Livewire::test(ListPesantrens::class)->instance()->getTableQueryForExport()
        ))->query()->pluck('id')->all();

        $this->assertCount(5, $lewatTabel);
        $this->assertSame($lewatTabel, array_values(array_unique($lewatTabel)));

        // Kelas Export juga harus aman dipakai tanpa query tabel Filament, yang
        // menyumbang tiebreaker id-nya sendiri. Di jalur ini orderBy('id') milik
        // konstruktor adalah satu-satunya yang membuat urutannya deterministik.
        $langsung = (new DataPesantrenExport(Pesantren::query()))->query()->pluck('id')->all();

        $this->assertSame(collect($langsung)->sort()->values()->all(), $langsung);
        $this->assertCount(5, $langsung);
    }

    // ---------- Isi kolom Data Pesantren ----------

    /**
     * Ustadz sengaja dibuat SEBELUM admin, dan itulah inti tesnya.
     *
     * Bentuk relasi `->where('role',...)->oldestOfMany()` yang terbaca benar
     * ternyata tidak menyalurkan filter role ke subquery agregat, sehingga
     * MIN(id) dihitung atas seluruh user lalu dibuang di outer query — kolom
     * Nama Admin jadi kosong padahal adminnya ada. Membuat admin lebih dulu
     * (urutan yang paling wajar ditulis di tes) menyembunyikan bug itu total.
     */
    public function test_kolom_admin_diambil_dari_admin_terlama_meski_ustadz_dibuat_duluan(): void
    {
        $this->actingAs($this->superAdmin());
        $pesantren = Pesantren::factory()->create();

        User::factory()->ustadz()->create([
            'pesantren_id' => $pesantren->id,
            'name' => 'Ustadz Yang Dibuat Duluan',
        ]);

        User::factory()->adminPesantren()->create([
            'pesantren_id' => $pesantren->id,
            'name' => 'Admin Pertama',
            'email' => 'pertama@example.com',
            'phone_number' => '08111111111',
        ]);
        User::factory()->adminPesantren()->create([
            'pesantren_id' => $pesantren->id,
            'name' => 'Admin Kedua',
        ]);
        User::factory()->waliSantri()->count(3)->create(['pesantren_id' => $pesantren->id]);

        $export = new DataPesantrenExport(Pesantren::query()->whereKey($pesantren->id));
        $baris = $this->baris($export, $export->query()->first());

        $this->assertSame('Admin Pertama', $baris['Nama Admin']);
        $this->assertSame('pertama@example.com', $baris['Email Admin']);
        $this->assertSame('08111111111', $baris['No. HP Admin']);
        $this->assertSame(2, $baris['Jumlah Admin']);
        $this->assertSame(1, $baris['Jumlah Ustadz']);
        $this->assertSame(3, $baris['Jumlah Wali Santri']);
    }

    public function test_pesantren_tanpa_admin_tidak_menggagalkan_ekspor(): void
    {
        $this->actingAs($this->superAdmin());
        $pesantren = Pesantren::factory()->create();

        $export = new DataPesantrenExport(Pesantren::query()->whereKey($pesantren->id));
        $baris = $this->baris($export, $export->query()->first());

        $this->assertSame('—', $baris['Nama Admin']);
        $this->assertSame('—', $baris['Email Admin']);
        $this->assertSame('—', $baris['Email Admin Terverifikasi']);
        $this->assertSame(0, $baris['Jumlah Admin']);
    }

    public function test_santri_aktif_dihitung_langsung_bukan_dari_kolom_cache(): void
    {
        $this->actingAs($this->superAdmin());
        $pesantren = Pesantren::factory()->create([
            'max_santri_kuota' => 100,
            'santri_count_cache' => 0, // sengaja dibiarkan basi
        ]);

        Santri::factory()->count(2)->create(['pesantren_id' => $pesantren->id, 'status_aktif' => true]);
        Santri::factory()->create(['pesantren_id' => $pesantren->id, 'status_aktif' => false]);

        $export = new DataPesantrenExport(Pesantren::query()->whereKey($pesantren->id));
        $baris = $this->baris($export, $export->query()->first());

        $this->assertSame(2, $baris['Santri Aktif']);
        $this->assertSame(98, $baris['Sisa Kuota']);
    }

    /**
     * withMax() mengembalikan string mentah dari database, BUKAN Carbon —
     * kolom `santri_terakhir` tidak ada di $casts. Kalau diformat tanpa
     * di-parse sebagai UTC lebih dulu, jamnya meleset 7 jam tanpa galat apa pun.
     */
    public function test_santri_terakhir_ditambahkan_dicetak_dalam_wib(): void
    {
        $this->actingAs($this->superAdmin());
        $pesantren = Pesantren::factory()->create();

        $santri = Santri::factory()->create(['pesantren_id' => $pesantren->id]);
        $santri->forceFill(['created_at' => SupportCarbon::parse('2026-07-22 20:00:00', 'UTC')])->saveQuietly();

        $export = new DataPesantrenExport(Pesantren::query()->whereKey($pesantren->id));
        $baris = $this->baris($export, $export->query()->first());

        $this->assertSame('23/07/2026 03:00', $baris['Santri Terakhir Ditambahkan']);
    }

    public function test_paket_dan_status_dicetak_sebagai_label_indonesia(): void
    {
        $this->actingAs($this->superAdmin());
        $pesantren = Pesantren::factory()->create([
            'paket_langganan' => 'tumbuh',
            'status_berlangganan' => 'active',
        ]);

        $export = new DataPesantrenExport(Pesantren::query()->whereKey($pesantren->id));
        $baris = $this->baris($export, $export->query()->first());

        $this->assertSame('Tumbuh', $baris['Paket']);
        $this->assertSame('Aktif', $baris['Status Langganan']);
    }

    // ---------- Isi kolom Antrean Demo ----------

    public function test_sla_di_berkas_sama_dengan_badge_di_tabel(): void
    {
        $this->actingAs($this->superAdmin());

        $overdue = $this->demoRequestAt(DemoRequest::slaCutoff()->subDay());
        $selesai = $this->demoRequestAt(now(), contactedAt: now());
        $berjalan = $this->demoRequestAt(now());

        $export = new AntreanDemoExport(DemoRequest::query());

        $this->assertSame('Overdue', $this->baris($export, $overdue)['SLA']);
        $this->assertSame('Selesai', $this->baris($export, $selesai)['SLA']);
        $this->assertSame('0 hr kerja', $this->baris($export, $berjalan)['SLA']);
    }

    public function test_lama_menunggu_berhenti_dihitung_setelah_dihubungi(): void
    {
        $this->actingAs($this->superAdmin());
        $selesai = $this->demoRequestAt(now()->subWeek(), contactedAt: now());

        $export = new AntreanDemoExport(DemoRequest::query());

        $this->assertSame('—', $this->baris($export, $selesai)['Lama Menunggu (hari kerja)']);
    }

    public function test_kolom_duplikat_menyebut_permintaan_asalnya(): void
    {
        $this->actingAs($this->superAdmin());

        $asal = $this->demoRequestAt(now(), atribut: ['nama_pesantren' => 'Pesantren Asal']);
        // Observer mendeteksi duplikat lewat email/no_hp yang sama dalam 30 hari.
        $duplikat = $this->demoRequestAt(now());

        $export = new AntreanDemoExport(DemoRequest::query());
        $baris = $this->baris($export, $export->query()->whereKey($duplikat->id)->first());

        $this->assertSame('#'.$asal->id.' — Pesantren Asal', $baris['Duplikat Dari']);
    }

    public function test_waktu_dicetak_menurut_jam_dinding_wib(): void
    {
        $this->actingAs($this->superAdmin());

        // 2026-07-22 20:00 UTC = 2026-07-23 03:00 WIB. Tanpa konversi, tanggalnya
        // akan tercetak mundur sehari.
        $record = $this->demoRequestAt(SupportCarbon::parse('2026-07-22 20:00:00', 'UTC'));

        $export = new AntreanDemoExport(DemoRequest::query());

        $this->assertSame('23/07/2026 03:00', $this->baris($export, $record)['Tanggal Daftar']);
    }

    // ---------- Gerbang akses ----------

    public function test_admin_pesantren_tidak_bisa_mengakses_permukaan_ekspor(): void
    {
        $pesantren = Pesantren::factory()->create();
        $this->actingAs(User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]));

        $this->assertFalse(PesantrenResource::canAccess());
        $this->assertFalse(DemoRequestResource::canAccess());
    }
}
