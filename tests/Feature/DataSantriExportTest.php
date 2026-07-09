<?php

namespace Tests\Feature;

use App\Exports\DataSantriExport;
use App\Models\Pesantren;
use App\Models\Santri;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'kelas', 'kamar',
        ];

        foreach ($importableKeys as $key) {
            $this->assertContains($key, $sluggedHeadings, "Kolom '{$key}' hilang dari heading export.");
        }
    }
}
