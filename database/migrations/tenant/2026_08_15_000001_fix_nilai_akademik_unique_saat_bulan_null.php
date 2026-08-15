<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Unique (santri_id, mata_pelajaran_id, tahun_ajaran, periode, bulan) yang dipasang
// 2026_06_28_000004 TIDAK menjaga apa pun untuk periode Semester_Ganjil/Semester_Genap,
// karena di sana `bulan` sengaja NULL — dan di dalam UNIQUE, NULL tidak pernah sama
// dengan NULL (berlaku di PostgreSQL MAUPUN SQLite, jadi tidak ada perbedaan engine
// yang akan membongkarnya di CI). Komentar di NilaiMassalPage sudah mengakui hal ini
// dan menyerahkan pencegahan duplikat ke updateOrCreate, padahal updateOrCreate adalah
// SELECT-lalu-INSERT yang tidak atomik: dua penyimpanan bersamaan menghasilkan dua baris
// nilai untuk santri + mapel + periode yang sama, dan rata-rata rapor ikut melenceng.
//
// Ditambal dengan partial unique index khusus kasus bulan IS NULL. PostgreSQL dan SQLite
// sama-sama mendukung sintaks ini, jadi tidak perlu cabang per-driver — dan justru itu
// yang membuat suite lokal ikut menguji perilakunya (bandingkan pelajaran CHECK
// constraint di PRD §22, yang cabang SQLite-nya dulu cuma `return`).
return new class extends Migration
{
    private const INDEX = 'nilai_akademik_unik_tanpa_bulan';

    public function up(): void
    {
        // Baris kembar yang telanjur lahir harus dibersihkan dulu, kalau tidak
        // CREATE UNIQUE INDEX-nya gagal. Yang dipertahankan adalah id TERKECIL —
        // itulah baris yang selama ini ditemukan updateOrCreate (`first()`) dan
        // karenanya yang nilainya benar-benar disunting pengguna; membuang yang
        // lain tidak mengubah angka yang selama ini mereka lihat di form.
        $terhapus = DB::table('nilai_akademik')
            ->whereNull('bulan')
            ->whereNotIn('id', function ($q) {
                $q->from('nilai_akademik')
                    ->selectRaw('MIN(id)')
                    ->whereNull('bulan')
                    ->groupBy('santri_id', 'mata_pelajaran_id', 'tahun_ajaran', 'periode');
            })
            ->delete();

        if ($terhapus > 0) {
            // Dicetak, bukan didiamkan: kalau angkanya tidak nol, berarti bug ini
            // sudah pernah benar-benar terjadi di data itu dan layak ditelusuri.
            echo "  [nilai_akademik] {$terhapus} baris duplikat (periode semester) dihapus sebelum unique index dipasang.\n";
        }

        DB::statement(
            'CREATE UNIQUE INDEX '.self::INDEX.' ON nilai_akademik '.
            '(santri_id, mata_pelajaran_id, tahun_ajaran, periode) WHERE bulan IS NULL'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS '.self::INDEX);
    }
};
