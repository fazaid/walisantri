<?php

namespace App\Exports;

use App\Models\DemoRequest;
use App\Support\Waktu;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Ekspor antrean demo untuk super admin.
 *
 * Berbeda dengan Export lain di folder ini, querynya datang dari LUAR —
 * `getTableQueryForExport()` milik halaman Filament — supaya filter,
 * pencarian, dan urutan yang sedang aktif di tabel ikut terbawa ke berkas.
 * Membangun query sendiri di sini berarti tombol Ekspor mengabaikan filter
 * yang baru saja dipasang super admin, dan itu justru kebalikan dari yang
 * ia harapkan.
 */
class AntreanDemoExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    private Builder $query;

    public function __construct(Builder $query)
    {
        // orderBy('id') BUKAN kosmetik. Jalur sinkron maatwebsite memakai
        // Builder::chunk() yang berbasis offset/limit, sementara sortir bawaan
        // tabel ini `created_at desc` tidak unik: baris ber-created_at kembar
        // bisa terduplikasi atau terlewat di batas chunk, tanpa galat apa pun.
        // Query yang datang dari getTableQueryForExport() sebenarnya sudah
        // dibubuhi tiebreaker id oleh Filament sendiri (CanSortRecords::
        // applySortingToTableQuery), dan baris ini jadi no-op di sana — ia
        // menjaga jalur pemakaian langsung, tempat tidak ada yang menjaganya.
        $this->query = $query->with('duplicateOf')->orderBy('id');
    }

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Tanggal Daftar',
            'Nama Pesantren',
            'Kota',
            'Nama Kontak',
            'Email',
            'No. HP',
            'Jumlah Santri',
            'Status Kontak',
            'Tanggal Dihubungi',
            'SLA',
            'Lama Menunggu (hari kerja)',
            'Duplikat Dari',
            'Catatan',
        ];
    }

    /**
     * @param  DemoRequest  $record
     */
    public function map($record): array
    {
        return [
            $record->id,
            self::waktu($record->created_at),
            $record->nama_pesantren,
            $record->kota ?: '—',
            $record->nama_kontak,
            $record->email,
            $record->no_hp,
            $record->jumlah_santri ?: '—',
            $record->contacted_at !== null ? 'Sudah Dihubungi' : 'Belum Dihubungi',
            self::waktu($record->contacted_at),
            self::sla($record),
            // Hanya diisi selama antrean masih berjalan. Setelah dihubungi,
            // businessDaysWaiting() tetap menghitung sampai HARI INI — angka
            // yang terus bertambah untuk pekerjaan yang sudah selesai. Model
            // tidak punya definisi "lama menunggu sampai dihubungi", dan
            // mengarangnya di sini melahirkan definisi SLA kedua.
            $record->contacted_at === null ? $record->businessDaysWaiting() : '—',
            $record->duplicateOf
                ? '#'.$record->duplicateOf->id.' — '.$record->duplicateOf->nama_pesantren
                : '—',
            $record->catatan ?: '—',
        ];
    }

    public function title(): string
    {
        return 'Antrean Demo';
    }

    /**
     * Derivasi yang PERSIS sama dengan badge SLA di DemoRequestsTable.
     * Kalau keduanya menyimpang, berkas dan layar akan menyatakan hal berbeda
     * tentang lead yang sama — pelajaran §15 PRD tentang halaman & ekspor yang
     * punya versi hitungan sendiri.
     */
    private static function sla(DemoRequest $record): string
    {
        return match (true) {
            $record->contacted_at !== null => 'Selesai',
            $record->isOverdue() => 'Overdue',
            default => $record->businessDaysWaiting().' hr kerja',
        };
    }

    /**
     * Timestamp disimpan UTC, seluruh pengguna platform di WIB. Tanpa konversi,
     * permintaan demo yang masuk 06.00 WIB tercetak bertanggal kemarin.
     */
    private static function waktu(?\DateTimeInterface $waktu): string
    {
        return $waktu
            ? Carbon::instance($waktu)->timezone(Waktu::zona())->format('d/m/Y H:i')
            : '—';
    }
}
