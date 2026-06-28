<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Filament\Clusters\Keuangan;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

class SaldoUangSakuPage extends Page
{
    use WithPagination;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $cluster = Keuangan::class;

    protected static ?string $navigationLabel = 'Saldo Santri';

    protected static ?string $title = 'Saldo Uang Saku Santri';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.saldo-uang-saku-page';

    public string $search = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::AdminPesantren->value;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function getData(): LengthAwarePaginator
    {
        $pesantrenId = auth()->user()->pesantren_id;

        $query = DB::table('santri')
            ->leftJoin('uang_saku_santri as uss', 'uss.santri_id', '=', 'santri.id')
            ->leftJoin('kelas', 'kelas.id', '=', 'santri.kelas_id')
            ->where('santri.pesantren_id', $pesantrenId)
            ->where('santri.status_aktif', true)
            ->whereNull('santri.deleted_at')
            ->groupBy('santri.id', 'santri.nama_lengkap', 'kelas.nama_kelas')
            ->orderBy('santri.nama_lengkap')
            ->select([
                'santri.id',
                'santri.nama_lengkap',
                'kelas.nama_kelas',
                DB::raw("COALESCE(SUM(CASE WHEN uss.jenis = 'setoran' THEN uss.nominal ELSE 0 END), 0) AS total_setoran"),
                DB::raw("COALESCE(SUM(CASE WHEN uss.jenis = 'pengambilan' THEN uss.nominal ELSE 0 END), 0) AS total_pengambilan"),
                DB::raw("COALESCE(SUM(CASE WHEN uss.jenis = 'setoran' THEN uss.nominal ELSE -uss.nominal END), 0) AS saldo"),
            ]);

        if ($this->search !== '') {
            $query->where('santri.nama_lengkap', 'ilike', '%' . $this->search . '%');
        }

        return $query->paginate(50);
    }

    public function getSummary(): object
    {
        $pesantrenId = auth()->user()->pesantren_id;

        return DB::table('santri')
            ->leftJoin('uang_saku_santri as uss', 'uss.santri_id', '=', 'santri.id')
            ->where('santri.pesantren_id', $pesantrenId)
            ->where('santri.status_aktif', true)
            ->whereNull('santri.deleted_at')
            ->selectRaw("
                COUNT(DISTINCT santri.id) AS total_santri,
                COALESCE(SUM(CASE WHEN uss.jenis = 'setoran' THEN uss.nominal ELSE -uss.nominal END), 0) AS total_saldo
            ")
            ->first();
    }
}
