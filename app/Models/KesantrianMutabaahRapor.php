<?php

namespace App\Models;

use App\Traits\Multitenantable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('kesantrian_mutabaah_rapor')]
#[Fillable([
    'pesantren_id',
    'santri_id',
    'bulan',
    'tahun',
    'total_hari_input',
    'total_hari_udzur',
    'udzur_detail',
    'ringkasan_amalan',
    'catatan',
])]
class KesantrianMutabaahRapor extends Model
{
    use Multitenantable;

    protected function casts(): array
    {
        return [
            'bulan'            => 'integer',
            'total_hari_input' => 'integer',
            'total_hari_udzur' => 'integer',
            'udzur_detail'     => 'array',
            'ringkasan_amalan' => 'array',
        ];
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class);
    }

    public function pesantren(): BelongsTo
    {
        return $this->belongsTo(Pesantren::class);
    }

    public function getNamaBulanAttribute(): string
    {
        $bulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        return $bulan[$this->bulan] ?? '-';
    }

    public function getPeriodeAttribute(): string
    {
        return $this->nama_bulan . ' ' . $this->tahun;
    }

    public function getRataRataPersenAttribute(): int
    {
        $ringkasan = $this->ringkasan_amalan ?? [];
        if (empty($ringkasan)) return 0;
        $total = array_sum(array_column($ringkasan, 'persen'));
        return (int) round($total / count($ringkasan));
    }

    public static function hitung(int $santriId, int $pesantrenId, int $bulan, string $tahun): array
    {
        $records = KesantrianMutabaah::where('santri_id', $santriId)
            ->whereYear('tanggal', $tahun)
            ->whereMonth('tanggal', $bulan)
            ->get();

        $totalHariInput = $records->count();
        $totalHariUdzur = $records->where('status_udzur', '!=', 'Tidak')->count();

        $udzurDetail = $records->where('status_udzur', '!=', 'Tidak')
            ->groupBy('status_udzur')
            ->map->count()
            ->toArray();

        $amalMasters = KesantrianAmalMaster::where('pesantren_id', $pesantrenId)
            ->where('aktif', true)
            ->orderBy('urutan')
            ->get();

        $ringkasanAmalan = [];
        foreach ($amalMasters as $master) {
            $kode = $master->kode;
            if ($master->tipe === 'hitungan') {
                $total = (int) $records->sum(fn ($r) => $r->amalan[$kode] ?? 0);
                $maks  = $totalHariInput * $master->nilai_maks;
                $persen = $maks > 0 ? (int) round($total / $maks * 100) : 0;
                $ringkasanAmalan[$kode] = [
                    'label'       => $master->label,
                    'icon'        => $master->icon,
                    'tipe'        => 'hitungan',
                    'nilai_maks'  => $master->nilai_maks,
                    'total_capai' => $total,
                    'total_maks'  => $maks,
                    'persen'      => $persen,
                ];
            } else {
                $total  = $records->filter(fn ($r) => !empty($r->amalan[$kode]))->count();
                $maks   = $totalHariInput;
                $persen = $maks > 0 ? (int) round($total / $maks * 100) : 0;
                $ringkasanAmalan[$kode] = [
                    'label'       => $master->label,
                    'icon'        => $master->icon,
                    'tipe'        => 'boolean',
                    'total_capai' => $total,
                    'total_maks'  => $maks,
                    'persen'      => $persen,
                ];
            }
        }

        return [
            'total_hari_input' => $totalHariInput,
            'total_hari_udzur' => $totalHariUdzur,
            'udzur_detail'     => $udzurDetail,
            'ringkasan_amalan' => $ringkasanAmalan,
        ];
    }
}
