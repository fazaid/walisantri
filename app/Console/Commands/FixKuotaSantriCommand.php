<?php

namespace App\Console\Commands;

use App\Enums\PaketLangganan;
use App\Models\BillingSetting;
use App\Models\Pesantren;
use Illuminate\Console\Command;

class FixKuotaSantriCommand extends Command
{
    protected $signature = 'billing:fix-kuota {--dry-run : Tampilkan perubahan tanpa mengeksekusi}';

    protected $description = 'Perbaiki max_santri_kuota tenant yang tidak sesuai dengan paket langganannya';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        // Diturunkan dari enum, bukan daftar tulis-tangan: versi lama hanya memuat
        // rintisan & berkembang, sehingga pesantren paket Tumbuh dilewati diam-diam
        // sejak paket itu ditambahkan.
        //
        // Paket Maju sengaja dikecualikan — kuotanya dihitung per-tenant lewat
        // formula §5.3, jadi max_santri_kuota yang menyimpang dari angka dasar di
        // sana justru kapasitas yang memang dibeli, bukan data rusak.
        $kuotaPerPaket = collect(PaketLangganan::cases())
            ->reject(fn (PaketLangganan $paket) => $paket === PaketLangganan::Maju)
            ->mapWithKeys(fn (PaketLangganan $paket) => [
                $paket->value => BillingSetting::get("kuota_{$paket->value}", $paket->maxSantri()),
            ])
            ->all();

        $this->info('Kuota dari BillingSetting:');
        foreach ($kuotaPerPaket as $paket => $kuota) {
            $this->line("  {$paket}: {$kuota} santri");
        }
        $this->newLine();

        $terdampak = Pesantren::whereIn('paket_langganan', array_keys($kuotaPerPaket))
            ->get()
            ->filter(fn ($p) => $p->max_santri_kuota !== $kuotaPerPaket[$p->paket_langganan]);

        if ($terdampak->isEmpty()) {
            $this->info('Tidak ada tenant yang perlu diperbaiki.');

            return self::SUCCESS;
        }

        $this->warn("Ditemukan {$terdampak->count()} tenant dengan kuota tidak sesuai:\n");

        $rows = $terdampak->map(fn ($p) => [
            $p->id,
            $p->nama_pesantren,
            $p->paket_langganan,
            $p->max_santri_kuota,
            $kuotaPerPaket[$p->paket_langganan],
            $p->status_berlangganan,
        ])->values()->toArray();

        $this->table(
            ['ID', 'Nama Pesantren', 'Paket', 'Kuota Lama', 'Kuota Benar', 'Status'],
            $rows,
        );

        if ($isDryRun) {
            $this->newLine();
            $this->warn('Mode dry-run: tidak ada perubahan yang disimpan.');

            return self::SUCCESS;
        }

        if (! $this->confirm("Lanjutkan perbaikan untuk {$terdampak->count()} tenant?")) {
            $this->info('Dibatalkan.');

            return self::SUCCESS;
        }

        $fixed = 0;
        foreach ($terdampak as $pesantren) {
            $kuotaBaru = $kuotaPerPaket[$pesantren->paket_langganan];
            $pesantren->update(['max_santri_kuota' => $kuotaBaru]);
            $this->line("  [✓] {$pesantren->nama_pesantren} → {$kuotaBaru} santri");
            $fixed++;
        }

        $this->newLine();
        $this->info("Selesai: {$fixed} tenant berhasil diperbaiki.");

        return self::SUCCESS;
    }
}
