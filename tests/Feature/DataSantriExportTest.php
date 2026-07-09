<?php

namespace Tests\Feature;

use App\Exports\DataSantriExport;
use App\Imports\SantriImport;
use App\Models\Pesantren;
use App\Models\Santri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Tests\TestCase;

class DataSantriExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_menyertakan_kolom_yang_bisa_diimpor_ulang(): void
    {
        $pesantren = Pesantren::factory()->create();
        $santri    = Santri::factory()->create([
            'pesantren_id'   => $pesantren->id,
            'alamat_lengkap' => 'Jl. Merpati No. 5',
            'jumlah_saudara' => 3,
            'cita_cita'      => 'Ulama',
        ]);

        $export = new DataSantriExport($pesantren->id);
        $row    = array_combine($export->headings(), $export->map($santri->fresh()));

        $this->assertSame('Jl. Merpati No. 5', $row['Alamat Lengkap']);
        $this->assertSame(3, $row['Jumlah Saudara']);
        $this->assertSame('Ulama', $row['Cita-Cita']);
    }

    public function test_heading_hasil_export_slug_sama_dengan_key_yang_dibaca_santri_import(): void
    {
        $pesantren = Pesantren::factory()->create();
        $santri    = Santri::factory()->create(['pesantren_id' => $pesantren->id]);

        $export  = new DataSantriExport($pesantren->id);
        $sluggedHeadings = array_map(fn (string $h) => Str::slug($h, '_'), $export->headings());

        // Kolom yang didukung SantriImport (App\Imports\SantriImport::collection())
        // harus tetap bisa ditemukan lewat header hasil export, supaya alur
        // export -> edit -> reimport tidak kehilangan field ini lagi.
        $importableKeys = [
            'nis', 'nama_lengkap', 'nama_panggilan', 'tanggal_lahir', 'jenis_kelamin',
            'nama_ayah', 'nama_ibu', 'alamat_lengkap', 'jumlah_saudara', 'cita_cita',
            'kelas', 'kamar', 'status',
        ];

        foreach ($importableKeys as $key) {
            $this->assertContains($key, $sluggedHeadings, "Kolom '{$key}' hilang dari heading export.");
        }
    }

    public function test_kolom_status_hasil_export_bisa_diimpor_ulang_dengan_benar(): void
    {
        $pesantren = Pesantren::factory()->create();
        $santriNonAktif = Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'status_aktif' => false,
        ]);

        $export  = new DataSantriExport($pesantren->id);
        $sluggedHeadings = array_map(fn (string $h) => Str::slug($h, '_'), $export->headings());
        $row     = array_combine($sluggedHeadings, $export->map($santriNonAktif->fresh()));

        $this->assertSame('Non-Aktif', $row['status']);

        // Simulasikan file hasil export ini diimpor ulang ke pesantren lain.
        $pesantrenLain = Pesantren::factory()->create();
        $import = new SantriImport($pesantrenLain->id);
        $import->collection(new Collection([
            ['nis' => $row['nis'], 'nama_lengkap' => $row['nama_lengkap'], 'status' => $row['status']],
        ]));

        $this->assertSame([], $import->errors);
        $this->assertFalse((bool) Santri::where('pesantren_id', $pesantrenLain->id)->first()->status_aktif);
    }
}
