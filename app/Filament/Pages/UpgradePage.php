<?php

namespace App\Filament\Pages;

use App\Enums\DurasiLangganan;
use App\Enums\PaketLangganan;
use App\Enums\TipeDiskon;
use App\Models\Kupon;
use App\Models\Pesantren;
use App\Services\BillingCalculatorService;
use App\Services\PaketHargaService;
use App\Services\UpgradeOrderService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class UpgradePage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpCircle;

    protected static ?string $navigationLabel = 'Upgrade Paket';

    protected static ?string $title = 'Upgrade / Perpanjang Paket';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.upgrade-page';

    // Form state
    public string $paket_target = '';

    public int $durasi_bulan = 1;

    public int $max_santri_kuota_target = 1000;

    // Kuota yang benar-benar akan diterima kalau order ini dibayar — selalu hasil
    // kalkulator, bukan isi kolom mentah (paket tetap mengabaikan angka ketikan,
    // dan Maju membulatkannya ke kelipatan 100).
    public int $kuota_target_efektif = 0;

    // Diambil sekali saat mount: pembanding untuk menolak paket yang kuotanya di
    // bawah jumlah santri yang sudah terlanjur masuk.
    public int $santri_aktif = 0;

    public string $kode_kupon = '';

    // Computed once in mount — minimum durasi berdasarkan sisa masa aktif
    public int $min_durasi_upgrade = 1;

    // Computed (reactive)
    public int $harga_per_bulan = 0;

    public int $harga_total_sebelum_diskon = 0;

    public int $diskon_nominal = 0;

    public ?int $diskon_persen = null;

    public int $bonus_bulan = 0;

    public int $harga_total = 0;

    public ?string $kupon_pesan = null;

    public bool $kupon_valid = false;

    public int $bulan_bayar = 1;

    public static function canAccess(): bool
    {
        return Auth::user()?->role === 'admin_pesantren';
    }

    public function mount(): void
    {
        $pesantren = Auth::user()->pesantren;

        abort_unless($pesantren, 403, 'Pesantren tidak ditemukan.');

        $this->paket_target = $pesantren->paket_langganan ?? 'rintisan';
        $this->max_santri_kuota_target = $pesantren->max_santri_kuota ?? 1000;
        $this->santri_aktif = $pesantren->jumlahSantriAktif();
        $this->min_durasi_upgrade = $this->hitungMinDurasi($pesantren);
        $this->durasi_bulan = max($this->durasi_bulan, $this->min_durasi_upgrade);

        // Paket & durasi tidak lagi hidup di schema Filament — keduanya dipilih lewat
        // kartu dan segmented control di Blade (wire:click), jadi nilainya murni
        // properti publik komponen ini.
        $this->form->fill([
            'max_santri_kuota_target' => $this->max_santri_kuota_target,
            'kode_kupon' => '',
        ]);

        $this->hitungHarga();
    }

    private function hitungMinDurasi(Pesantren $pesantren): int
    {
        if (! $pesantren->expired_at || ! $pesantren->expired_at->isFuture()) {
            return 1;
        }

        $sisaBulan = (int) ceil(now()->floatDiffInMonths($pesantren->expired_at));

        if ($sisaBulan > 9) {
            return 12;
        }
        if ($sisaBulan > 6) {
            return 6;
        }

        return 1;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Kuota Santri')
                ->description('Kuota paket Maju ditambah per 100 santri (§5.3).')
                ->visible(fn () => $this->paket_target === 'maju')
                ->schema([
                    TextInput::make('max_santri_kuota_target')
                        ->label('Kuota Santri')
                        ->numeric()
                        ->minValue(1000)
                        ->step(100)
                        ->helperText('Minimum 1.000 untuk paket Maju, kelipatan 100.')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (?string $state) {
                            $this->max_santri_kuota_target = (int) ($state ?? 1000);
                            $this->hitungHarga();
                        }),
                ]),

            Section::make('Kode Kupon')
                ->description('Opsional — masukkan kode promo jika ada.')
                ->schema([
                    TextInput::make('kode_kupon')
                        ->label('Kode Kupon')
                        ->placeholder('Contoh: DISKON50')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (?string $state) {
                            $this->kode_kupon = strtoupper(trim($state ?? ''));
                            $this->terapkanKupon();
                        })
                        ->suffixAction(
                            Action::make('terapkanKupon')
                                ->label('Terapkan')
                                ->icon(Heroicon::OutlinedCheck)
                                ->button()
                                ->action(function () {
                                    $this->kode_kupon = strtoupper(trim($this->kode_kupon));
                                    $this->terapkanKupon();
                                })
                        ),
                ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])->id('form'),
        ]);
    }

    /**
     * Dirender di blade tepat di bawah kartu Ringkasan Biaya, bukan sebagai footer
     * form — supaya saat kolom di-stack di mobile, tombol tetap ada di bawah ringkasan.
     */
    public function prosesPembayaranAction(): Action
    {
        return Action::make('prosesPembayaran')
            ->label('Lakukan Pembayaran')
            ->icon(Heroicon::OutlinedCreditCard)
            ->color('primary')
            ->size(Size::Large)
            ->extraAttributes(['style' => 'width: 100%; justify-content: center;'])
            ->disabled(fn () => $this->kuotaKurang())
            ->action('prosesPembayaran');
    }

    /**
     * Kartu paket untuk siklus yang sedang dipilih.
     *
     * Angkanya lewat PaketHargaService — sumber yang sama dengan /harga, supaya
     * halaman ini tidak pernah memajang tarif yang berbeda dari yang dilihat calon
     * pelanggan sebelum masuk. Yang ditambahkan di sini hanya tiga penanda yang
     * memang cuma berarti di dalam panel: mana yang sedang dipilih, mana paket
     * tenant hari ini, dan mana yang terkunci karena kuotanya sudah terlampaui.
     *
     * @return list<array<string, mixed>>
     */
    public function kartuPaket(): array
    {
        $paketSekarang = Auth::user()?->pesantren?->paket_langganan;

        return collect(app(PaketHargaService::class)
            ->kartuUntukDurasi(DurasiLangganan::from($this->durasi_bulan), $this->max_santri_kuota_target))
            ->map(function (array $kartu) use ($paketSekarang) {
                $terkunci = $kartu['kuota'] < $this->santri_aktif;

                return $kartu + [
                    'terpilih' => $kartu['slug'] === $this->paket_target,
                    'sekarang' => $kartu['slug'] === $paketSekarang,
                    'terkunci' => $terkunci,
                    'alasanTerkunci' => $terkunci
                        ? 'Kuotanya di bawah '.number_format($this->santri_aktif, 0, ',', '.').' santri aktif Anda.'
                        : null,
                ];
            })
            ->all();
    }

    /**
     * Pilihan siklus. Durasi di bawah min_durasi_upgrade ditandai terkunci, bukan
     * dihilangkan: tombol yang lenyap tanpa penjelasan terbaca sebagai "opsinya
     * tidak ada", padahal yang benar "sisa langganan Anda masih panjang".
     *
     * Bonus bulan gratis tidak ikut dikembalikan: yang menuliskannya kartu paket,
     * lengkap dengan total yang ditagih — tab cukup menyebut lama siklusnya.
     *
     * @return list<array{bulan: int, label: string, terpilih: bool, terkunci: bool}>
     */
    public function opsiDurasi(): array
    {
        return collect(DurasiLangganan::cases())
            ->map(fn (DurasiLangganan $durasi) => [
                'bulan' => $durasi->value,
                'label' => $durasi->label(),
                'terpilih' => $durasi->value === $this->durasi_bulan,
                'terkunci' => $durasi->value < $this->min_durasi_upgrade,
            ])
            ->all();
    }

    public function pesanMinDurasi(): ?string
    {
        return match ($this->min_durasi_upgrade) {
            12 => 'Durasi minimum 12 bulan karena sisa langganan aktif lebih dari 9 bulan.',
            6 => 'Durasi minimum 6 bulan karena sisa langganan aktif lebih dari 6 bulan.',
            default => null,
        };
    }

    public function pilihPaket(string $paket): void
    {
        $pilihan = PaketLangganan::tryFrom($paket);

        if (! $pilihan) {
            return;
        }

        $kuota = app(BillingCalculatorService::class)
            ->hitungUntukTarget($pilihan->value, $this->max_santri_kuota_target)['kuota_maksimal'];

        // Kartunya memang sudah dinonaktifkan di Blade, tapi wire:click tetap bisa
        // dipanggil langsung dari klien — sama alasannya dengan pagar di
        // prosesPembayaran().
        if ($kuota < $this->santri_aktif) {
            Notification::make()
                ->title('Kuota paket tidak cukup')
                ->body('Paket '.$pilihan->label().' berkuota '.number_format($kuota, 0, ',', '.')
                    .' santri, di bawah '.number_format($this->santri_aktif, 0, ',', '.').' santri aktif Anda.')
                ->danger()
                ->send();

            return;
        }

        $this->paket_target = $pilihan->value;
        $this->max_santri_kuota_target = $pilihan === PaketLangganan::Maju
            ? max($kuota, 1000)
            : $kuota;

        $this->form->fill([
            'max_santri_kuota_target' => $this->max_santri_kuota_target,
            'kode_kupon' => $this->kode_kupon,
        ]);

        $this->hitungHarga();
    }

    public function pilihDurasi(int $bulan): void
    {
        $durasi = DurasiLangganan::tryFrom($bulan);

        // Pagar ini dulu dipegang ->options() milik Select durasi, yang memfilter
        // daftar dengan min_durasi_upgrade. Begitu dropdown-nya diganti tombol,
        // pagarnya harus pindah ke sini — bukan hilang.
        if (! $durasi || $durasi->value < $this->min_durasi_upgrade) {
            return;
        }

        $this->durasi_bulan = $durasi->value;

        // hitungHarga() memanggil terapkanKupon() di ujungnya; validitas kupon
        // bergantung durasi (Kupon::isValid), jadi urutannya tidak boleh dibalik.
        $this->hitungHarga();
    }

    /**
     * Paket tujuan tidak boleh berkuota di bawah santri yang sudah aktif.
     *
     * SantriObserver hanya bisa menahan PENAMBAHAN santri (SantriQuotaExceededException);
     * tidak ada mekanisme apa pun yang membereskan kelebihan yang terlanjur ada. Jadi
     * pagarnya dipasang di sini — sebelum ordernya lahir — bukan sesudah pembayaran
     * dikonfirmasi dan tenant menemukan dirinya 300 santri di kuota 100.
     */
    public function kuotaKurang(): bool
    {
        return $this->kuota_target_efektif > 0
            && $this->kuota_target_efektif < $this->santri_aktif;
    }

    public function pesanKuotaKurang(): string
    {
        return 'Pesantren Anda punya '.number_format($this->santri_aktif, 0, ',', '.')
            .' santri aktif, melebihi kuota '.number_format($this->kuota_target_efektif, 0, ',', '.')
            .' pada paket ini. Pilih paket yang kuotanya cukup, atau nonaktifkan santri yang sudah tidak mondok.';
    }

    public function hitungHarga(): void
    {
        $calculator = app(BillingCalculatorService::class);
        $hasil = $calculator->hitungUntukTarget($this->paket_target, $this->max_santri_kuota_target);

        $durasi = DurasiLangganan::from($this->durasi_bulan);
        $this->kuota_target_efektif = $hasil['kuota_maksimal'];
        $this->harga_per_bulan = $hasil['total_biaya'];
        $this->bonus_bulan = $durasi->bonusBulan();
        $this->bulan_bayar = $durasi->bulanBayar();
        $this->harga_total_sebelum_diskon = $this->harga_per_bulan * $this->bulan_bayar;

        $this->terapkanKupon();
    }

    public function terapkanKupon(): void
    {
        if (empty($this->kode_kupon)) {
            $this->diskon_nominal = 0;
            $this->diskon_persen = null;
            $this->kupon_pesan = null;
            $this->kupon_valid = false;
            $this->harga_total = $this->harga_total_sebelum_diskon;

            return;
        }

        $kupon = Kupon::where('kode', strtoupper($this->kode_kupon))->first();

        if (! $kupon || ! $kupon->isValid($this->durasi_bulan)) {
            $this->diskon_nominal = 0;
            $this->diskon_persen = null;
            $this->kupon_pesan = 'Kode kupon tidak valid atau sudah kadaluwarsa.';
            $this->kupon_valid = false;
            $this->harga_total = $this->harga_total_sebelum_diskon;

            return;
        }

        $this->diskon_nominal = $kupon->hitungDiskon($this->harga_total_sebelum_diskon);
        $this->diskon_persen = $kupon->tipe_diskon === TipeDiskon::Persentase ? $kupon->nilai_diskon : null;
        $this->harga_total = max(0, $this->harga_total_sebelum_diskon - $this->diskon_nominal);
        $this->kupon_valid = true;
        $this->kupon_pesan = 'Kupon berhasil diterapkan!';
    }

    public function prosesPembayaran(): void
    {
        $this->form->getState();

        // Tombolnya memang sudah dimatikan, tapi aksi Livewire tetap bisa dipanggil
        // langsung. Notification, bukan abort(): ini kesalahan pilihan pengguna yang
        // masih bisa diperbaiki di halaman yang sama, bukan permintaan yang cacat.
        if ($this->kuotaKurang()) {
            Notification::make()
                ->title('Kuota paket tidak cukup')
                ->body($this->pesanKuotaKurang())
                ->danger()
                ->send();

            return;
        }

        abort_if(
            $this->durasi_bulan < $this->min_durasi_upgrade,
            422,
            "Durasi minimum upgrade adalah {$this->min_durasi_upgrade} bulan karena sisa langganan aktif Anda."
        );

        $pesantren = Auth::user()->pesantren;

        $service = app(UpgradeOrderService::class);
        $result = $service->createOrder(
            pesantren: $pesantren,
            paketTarget: $this->paket_target,
            durasibulan: $this->durasi_bulan,
            maxSantriKuota: $this->max_santri_kuota_target,
            kodeKupon: $this->kode_kupon ?: null,
        );

        Notification::make()
            ->title('Order berhasil dibuat!')
            ->body('Silakan lakukan pembayaran sesuai instruksi di halaman invoice.')
            ->success()
            ->send();

        $this->redirect(OrderInvoicePage::getUrl(['order' => $result['order']->id]));
    }

    public function formatRupiah(int $nilai): string
    {
        return 'Rp '.number_format($nilai, 0, ',', '.');
    }
}
