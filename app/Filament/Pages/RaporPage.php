<?php

namespace App\Filament\Pages;

use App\Enums\Modul;
use App\Filament\Support\ModulKomponen;
use App\Filament\Support\SantriOptions;
use App\Models\Santri;
use App\Services\Rapor\RaporAkademikData;
use App\Services\Rapor\RaporKarakterData;
use App\Services\Rapor\RaporMutabaahData;
use App\Services\Rapor\RaporPresensiData;
use App\Services\Rapor\RaporTahfidzData;
use App\Services\TahunAjaranOptions;
use App\Support\Waktu;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class RaporPage extends Page
{
    /** Modul rapor yang tersedia, urutannya menentukan urutan tampil & halaman PDF. */
    public const MODUL = [
        'akademik' => 'Akademik',
        'tahfidz' => 'Tahfidz',
        'mutabaah' => 'Mutabaah',
        'karakter' => 'Karakter',
        'presensi' => 'Presensi',
    ];

    /**
     * Kunci rapor → tuas modul yang memilikinya (v4.57).
     *
     * ⚠️ Perhatikan 'mutabaah' DAN 'karakter' sama-sama menggantung ke Kesantrian —
     * keduanya memang resource di Cluster Kesantrian, jadi mematikan modul itu
     * menghilangkan DUA centang sekaligus, bukan satu.
     *
     * Ditulis sebagai const pendamping, bukan menggantikan MODUL: MODUL tetap satu
     * sumber untuk label, urutan tampil, dan judul halaman PDF.
     */
    private const MODUL_TOGGLE = [
        'akademik' => Modul::Akademik,
        'tahfidz' => Modul::Tahfidz,
        'mutabaah' => Modul::Kesantrian,
        'karakter' => Modul::Kesantrian,
        'presensi' => Modul::Presensi,
    ];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;

    protected static ?string $slug = 'rapor';

    protected static ?string $navigationLabel = 'Rapor';

    protected static ?string $title = 'Rapor Santri';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.rapor-page';

    public ?int $santriId = null;

    public string $tahunAjaran = '';

    public string $periode = '';

    public string $bulan = '';

    /** @var array<int, string> modul yang dicentang untuk ditampilkan & dicetak */
    public array $modul = [];

    /** Cache per-request supaya blade boleh memanggil getData() berkali-kali tanpa query ulang. */
    private ?array $dataCache = null;

    /** Cache per-request; blade memanggil getModulOptions() dua kali per render. */
    private ?array $tersediaCache = null;

    public function mount(): void
    {
        $this->tahunAjaran = TahunAjaranOptions::current();
        $this->periode = TahunAjaranOptions::currentPeriode();
        $this->bulan = Waktu::sekarang()->month.'-'.Waktu::sekarang()->year;
        $this->modul = array_keys($this->modulTersedia());
    }

    public static function canAccess(): bool
    {
        return in_array(Auth::user()?->role, ['admin_pesantren', 'ustadz'])
            && ModulKomponen::aktif(static::class);
    }

    public function updated(string $property): void
    {
        // Filter apa pun berubah → hasil lama tidak berlaku lagi.
        $this->dataCache = null;

        if ($property === 'tahunAjaran' && ! array_key_exists($this->bulan, $this->getBulanOptions())) {
            $this->bulan = array_key_first($this->getBulanOptions());
        }
    }

    public function pilihSemuaModul(): void
    {
        $this->modul = array_keys($this->modulTersedia());
    }

    public function kosongkanModul(): void
    {
        $this->modul = [];
    }

    public function getModulOptions(): array
    {
        return $this->modulTersedia();
    }

    /**
     * Modul rapor yang modulnya masih dinyalakan pesantren ini.
     *
     * @return array<string, string>
     */
    public function modulTersedia(): array
    {
        return $this->tersediaCache ??= array_filter(
            self::MODUL,
            fn (string $kunci): bool => self::MODUL_TOGGLE[$kunci]->aktif(),
            ARRAY_FILTER_USE_KEY
        );
    }

    public function getSantriOptions(): array
    {
        return SantriOptions::untukRapor();
    }

    public function getTahunAjaranOptions(): array
    {
        return TahunAjaranOptions::options();
    }

    public function getPeriodeOptions(): array
    {
        return TahunAjaranOptions::periodeOptions();
    }

    public function getBulanOptions(): array
    {
        return TahunAjaranOptions::bulanOptions($this->tahunAjaran);
    }

    public function getPeriodeLabel(): string
    {
        return TahunAjaranOptions::labelPeriode($this->periode, $this->bulan, $this->tahunAjaran);
    }

    public function getSantri(): ?Santri
    {
        if (! $this->santriId) {
            return null;
        }

        // Dropdown sudah dibatasi, tapi santriId datang dari input klien —
        // pencocokan ulang mencegah santri di luar cakupan ikut terbuka.
        if (! array_key_exists($this->santriId, $this->getSantriOptions())) {
            return null;
        }

        return Santri::with('pesantren', 'kelas')->find($this->santriId);
    }

    /**
     * ⚠️ Baris kedua adalah SATU-SATUNYA titik yang menutup request basi.
     *
     * $this->modul adalah properti Livewire publik, jadi sepenuhnya dikendalikan
     * klien: tab yang dibuka sebelum admin mematikan Kesantrian, atau request yang
     * dirakit tangan, tetap bisa membawa 'mutabaah'. Karena getData() menyaring
     * SETIAP sumbernya lewat method ini, satu pemeriksaan di sini menutup layar dan
     * PDF sekaligus. Meng-inline-kan in_array() kembali ke getData() akan
     * menghilangkan penjagaan ini tanpa galat apa pun.
     */
    public function isModulAktif(string $modul): bool
    {
        return in_array($modul, $this->modul, true)
            && array_key_exists($modul, $this->modulTersedia());
    }

    /**
     * Data seluruh modul yang dicentang, dengan kunci sesuai self::MODUL.
     *
     * @return array<string, array>
     */
    public function getData(): array
    {
        if ($this->dataCache !== null) {
            return $this->dataCache;
        }

        $santri = $this->getSantri();

        if (! $santri) {
            return $this->dataCache = [];
        }

        $argumen = [$santri->id, $this->tahunAjaran, $this->periode, $this->periode === 'Bulanan' ? $this->bulan : null];

        $sumber = [
            'akademik' => RaporAkademikData::class,
            'tahfidz' => RaporTahfidzData::class,
            'mutabaah' => RaporMutabaahData::class,
            'karakter' => RaporKarakterData::class,
            'presensi' => RaporPresensiData::class,
        ];

        $data = [];

        foreach ($sumber as $modul => $kelas) {
            if ($this->isModulAktif($modul)) {
                $data[$modul] = $kelas::untuk(...$argumen);
            }
        }

        return $this->dataCache = $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('unduhPdf')
                ->label('Unduh PDF')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('primary')
                ->action(function () {
                    $santri = $this->getSantri();
                    $data = $this->getData();
                    $adaIsi = collect($data)->contains(fn (array $modul) => $modul['ada_data']);

                    if (! $santri || ! $adaIsi) {
                        Notification::make()
                            ->title('Belum ada data rapor untuk pilihan ini')
                            ->warning()
                            ->send();

                        return;
                    }

                    $pdf = Pdf::loadView('filament.pdf.rapor-gabungan', [
                        'santri' => $santri,
                        'data' => $data,
                        'modulLabels' => $this->modulTersedia(),
                        'tahunAjaran' => $this->tahunAjaran,
                        'periodeLabel' => $this->getPeriodeLabel(),
                    ])->setPaper('A4', 'portrait');

                    $filename = 'Rapor-'
                        .str_replace(' ', '-', $santri->nama_lengkap)
                        .'-'.str_replace('/', '-', $this->tahunAjaran)
                        .'-'.str_replace(' ', '-', $this->getPeriodeLabel())
                        .'.pdf';

                    return response()->streamDownload(
                        fn () => print ($pdf->output()),
                        $filename,
                        ['Content-Type' => 'application/pdf'],
                    );
                }),
        ];
    }
}
