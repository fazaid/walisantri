<?php

namespace App\Filament\Pages;

use App\Enums\StatusKehadiran;
use App\Enums\UserRole;
use App\Filament\Clusters\Presensi as ClusterPresensi;
use App\Models\Kelas;
use App\Services\PresensiRekap;
use App\Services\TahunAjaranOptions;
use App\Support\PenugasanUstadz;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class PresensiRekapPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $cluster = ClusterPresensi::class;

    protected static ?string $navigationLabel = 'Rekap';

    protected static ?string $title = 'Rekap Kehadiran';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'rekap';

    protected string $view = 'filament.pages.presensi-rekap-page';

    public ?string $tahun_ajaran = null;

    public ?string $periode = null;

    public ?string $bulan = null;

    public ?string $kelas_id = null;

    public static function canAccess(): bool
    {
        return in_array(Auth::user()?->role, [
            UserRole::AdminPesantren->value,
            UserRole::Ustadz->value,
        ], true);
    }

    public function mount(): void
    {
        $this->tahun_ajaran = TahunAjaranOptions::current();
        $this->periode = TahunAjaranOptions::currentPeriode();
    }

    public function updated(): void
    {
        // Bulan hanya bermakna untuk periode Bulanan; membiarkannya terisi saat
        // periode diganti membuat rentang diam-diam menyempit ke satu bulan.
        if ($this->periode !== 'Bulanan') {
            $this->bulan = null;
        }
    }

    /** @return array<string, string> */
    public function tahunAjaranOptions(): array
    {
        return TahunAjaranOptions::options();
    }

    /** @return array<string, string> */
    public function periodeOptions(): array
    {
        return TahunAjaranOptions::periodeOptions();
    }

    /** @return array<string, string> */
    public function bulanOptions(): array
    {
        return TahunAjaranOptions::bulanOptions($this->tahun_ajaran);
    }

    /** Ustadz hanya melihat kelas perwaliannya — cakupan yang sama dengan Isi Presensi. */
    public function kelasOptions(): array
    {
        $query = Kelas::query();

        if (Auth::user()?->role === UserRole::Ustadz->value) {
            $query->whereIn('id', PenugasanUstadz::kelasIdsPerwalian());
        }

        return $query->orderBy('nama_kelas')->pluck('nama_kelas', 'id')->all();
    }

    protected function rekap(): PresensiRekap
    {
        [$awal, $akhir] = TahunAjaranOptions::rentangTanggal(
            $this->tahun_ajaran ?? TahunAjaranOptions::current(),
            $this->periode ?? 'Semester_Ganjil',
            $this->bulan,
        );

        return PresensiRekap::untuk(
            Auth::user()->pesantren_id,
            $awal->toDateString(),
            $akhir->toDateString(),
            $this->kelas_id ? (int) $this->kelas_id : null,
            Auth::user()?->role === UserRole::Ustadz->value ? Auth::id() : null,
        );
    }

    /** @return Collection<int, object> */
    public function getBaris(): Collection
    {
        return $this->rekap()->perSantri();
    }

    public function getRingkasan(): object
    {
        return $this->rekap()->ringkasan();
    }

    /** @return Collection<int, object> */
    public function getPerluPerhatian(): Collection
    {
        return $this->rekap()->alpaBeruntun();
    }

    /** @return list<StatusKehadiran> */
    public function statusList(): array
    {
        return StatusKehadiran::cases();
    }

    public function kolomStatus(StatusKehadiran $status): string
    {
        return 'jml_'.strtolower($status->value);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ekspor')
                ->label('Ekspor Excel')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->url(fn (): string => route('admin.export.presensi', array_filter([
                    'tahun_ajaran' => $this->tahun_ajaran,
                    'periode' => $this->periode,
                    'bulan' => $this->bulan,
                    'kelas_id' => $this->kelas_id,
                ]))),
        ];
    }
}
