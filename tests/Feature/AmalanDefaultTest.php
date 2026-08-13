<?php

namespace Tests\Feature;

use App\Models\KesantrianAmalMaster;
use App\Models\Pesantren;
use App\Services\MutabaahScoreCalculator;
use App\Services\OnboardPesantren;
use App\Support\AmalanDefault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AmalanDefaultTest extends TestCase
{
    use RefreshDatabase;

    private function amalanMilik(int $pesantrenId)
    {
        return KesantrianAmalMaster::withoutGlobalScope('pesantren')
            ->where('pesantren_id', $pesantrenId)
            ->orderBy('urutan')
            ->get();
    }

    private function daftarkanPesantren(string $slug = 'pesantren-baru'): Pesantren
    {
        return app(OnboardPesantren::class)->execute(
            namaPesantren: 'Pesantren Baru',
            slug: $slug,
            adminName: 'Admin Baru',
            adminEmail: $slug.'@contoh.test',
            adminPassword: 'rahasia123',
        )['pesantren'];
    }

    public function test_pesantren_baru_langsung_punya_amalan_bawaan(): void
    {
        // Sebelum perbaikan, 7 amalan default hanya di-insert di dalam migrasi
        // untuk pesantren yang ada saat itu — pendaftar berikutnya dapat nol baris
        // dan modul Mutaba'ah-nya lumpuh tanpa pesan error.
        $pesantren = $this->daftarkanPesantren();

        $amalan = $this->amalanMilik($pesantren->id);

        $this->assertCount(7, $amalan);
        $this->assertSame(
            ['jamaah_5_waktu', 'is_rawatib', 'is_shalat_malam', 'is_dhuha', 'is_tilawah_1juz', 'is_infak', 'is_puasa'],
            $amalan->pluck('kode')->all(),
        );
        $this->assertTrue($amalan->every(fn ($a) => $a->aktif));
    }

    public function test_bobot_dan_tipe_berjamaah_tidak_ikut_seragam(): void
    {
        // Bobot yang salah bikin seluruh persentase mutaba'ah ngawur, dan ini
        // justru bagian yang mustahil ditebak admin kalau harus mengisi manual.
        $pesantren = $this->daftarkanPesantren();

        $berjamaah = $this->amalanMilik($pesantren->id)->firstWhere('kode', 'jamaah_5_waktu');

        $this->assertSame('hitungan', $berjamaah->tipe);
        $this->assertSame(5, $berjamaah->nilai_maks);
        $this->assertSame(25, $berjamaah->bobot);
        $this->assertSame('waktu', $berjamaah->satuan);

        $this->amalanMilik($pesantren->id)
            ->where('kode', '!=', 'jamaah_5_waktu')
            ->each(function ($amalan) {
                $this->assertSame('boolean', $amalan->tipe);
                $this->assertSame(7, $amalan->bobot);
                $this->assertNull($amalan->nilai_maks);
            });
    }

    public function test_skor_maksimal_tidak_lagi_nol(): void
    {
        // Gejala paling kasat mata dari bug ini: MutabaahScoreCalculator::maxScore()
        // menjumlah bobot amal master, jadi daftar kosong -> semua persentase 0%.
        $pesantren = $this->daftarkanPesantren();

        $this->assertSame(67, MutabaahScoreCalculator::maxScore($pesantren->id));
    }

    public function test_pemanggilan_ulang_tidak_menggandakan_atau_menimpa_kustomisasi(): void
    {
        $pesantren = $this->daftarkanPesantren();

        $rawatib = $this->amalanMilik($pesantren->id)->firstWhere('kode', 'is_rawatib');
        $rawatib->update(['label' => 'Rawatib Muakkad', 'bobot' => 15, 'aktif' => false]);

        AmalanDefault::untukPesantren($pesantren->id);

        $amalan = $this->amalanMilik($pesantren->id);
        $this->assertCount(7, $amalan, 'firstOrCreate tidak boleh menggandakan baris');

        $rawatibSetelah = $amalan->firstWhere('kode', 'is_rawatib');
        $this->assertSame('Rawatib Muakkad', $rawatibSetelah->label);
        $this->assertSame(15, $rawatibSetelah->bobot);
        $this->assertFalse($rawatibSetelah->aktif);
    }

    public function test_amalan_tidak_bocor_antar_pesantren(): void
    {
        $a = $this->daftarkanPesantren('pesantren-a');
        $b = $this->daftarkanPesantren('pesantren-b');

        $this->assertCount(7, $this->amalanMilik($a->id));
        $this->assertCount(7, $this->amalanMilik($b->id));
        $this->assertNotEquals(
            $this->amalanMilik($a->id)->pluck('id')->all(),
            $this->amalanMilik($b->id)->pluck('id')->all(),
        );
    }

    public function test_migrasi_perbaikan_hanya_menambal_yang_kosong(): void
    {
        // Bagian paling berisiko dari migrasi tambalan: salah seleksi bisa
        // menggandakan baris, atau menghidupkan ulang amalan yang sudah sengaja
        // dinonaktifkan pesantren yang datanya sudah lengkap.
        $kosong = Pesantren::factory()->create(['slug' => 'belum-punya']);

        $sudahPunya = Pesantren::factory()->create(['slug' => 'sudah-punya']);
        AmalanDefault::untukPesantren($sudahPunya->id);
        $this->amalanMilik($sudahPunya->id)
            ->firstWhere('kode', 'is_dhuha')
            ->update(['aktif' => false, 'label' => 'Dhuha (libur)']);

        $migrasi = require database_path(
            'migrations/tenant/2026_08_13_000003_seed_amal_master_untuk_pesantren_yang_kosong.php'
        );
        $migrasi->up();

        // Yang kosong tertambal penuh.
        $this->assertCount(7, $this->amalanMilik($kosong->id));

        // Yang sudah punya tidak digandakan dan kustomisasinya utuh.
        $amalanLama = $this->amalanMilik($sudahPunya->id);
        $this->assertCount(7, $amalanLama);
        $dhuha = $amalanLama->firstWhere('kode', 'is_dhuha');
        $this->assertFalse($dhuha->aktif);
        $this->assertSame('Dhuha (libur)', $dhuha->label);
    }

    public function test_pesantren_yang_dibuat_di_luar_alur_registrasi_bisa_ditambal(): void
    {
        // Ini jalur yang dipakai migrasi perbaikan untuk menyembuhkan pesantren
        // yang sudah telanjur mendaftar sebelum kebocoran ini ditutup.
        $pesantren = Pesantren::factory()->create();

        $this->assertCount(0, $this->amalanMilik($pesantren->id));

        AmalanDefault::untukPesantren($pesantren->id);

        $this->assertCount(7, $this->amalanMilik($pesantren->id));
    }
}
