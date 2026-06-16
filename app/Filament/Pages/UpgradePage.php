<?php

namespace App\Filament\Pages;

use App\Enums\DurasiLangganan;
use App\Enums\PaketLangganan;
use App\Models\Kupon;
use App\Services\BillingCalculatorService;
use App\Services\UpgradeOrderService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class UpgradePage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpCircle;
    protected static ?string $navigationLabel = 'Upgrade Paket';
    protected static ?string $title           = 'Upgrade / Perpanjang Paket';
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.upgrade-page';

    // Form state
    public string $paket_target            = '';
    public int    $durasi_bulan            = 1;
    public int    $max_santri_kuota_target = 1100;
    public string $kode_kupon              = '';

    // Computed (reactive)
    public int    $harga_per_bulan             = 0;
    public int    $harga_total_sebelum_diskon  = 0;
    public int    $diskon_nominal              = 0;
    public int    $bonus_bulan                 = 0;
    public int    $harga_total                 = 0;
    public ?string $kupon_pesan                = null;
    public bool   $kupon_valid                 = false;
    public int    $bulan_bayar                 = 1;

    public static function canAccess(): bool
    {
        return Auth::user()?->role === 'admin_pesantren';
    }

    public function mount(): void
    {
        $pesantren = Auth::user()->pesantren;
        $this->paket_target            = $pesantren->paket_langganan ?? 'rintisan';
        $this->max_santri_kuota_target = $pesantren->max_santri_kuota ?? 1100;

        $this->form->fill([
            'paket_target'            => $this->paket_target,
            'durasi_bulan'            => $this->durasi_bulan,
            'max_santri_kuota_target' => $this->max_santri_kuota_target,
            'kode_kupon'              => '',
        ]);

        $this->hitungHarga();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Pilih Paket')
                ->schema([
                    Select::make('paket_target')
                        ->label('Paket Tujuan')
                        ->options(
                            collect(PaketLangganan::cases())
                                ->filter(fn ($p) => $p !== PaketLangganan::Gratis)
                                ->mapWithKeys(fn ($p) => [$p->value => $p->label()])
                                ->all()
                        )
                        ->required()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function (?string $state) {
                            $this->paket_target = $state ?? '';
                            $calculator = app(BillingCalculatorService::class);
                            $hasil = $calculator->hitungUntukTarget($state ?? '', $this->max_santri_kuota_target);
                            $this->max_santri_kuota_target = $hasil['kuota_maksimal'];
                            if ($state === 'maju') {
                                $this->max_santri_kuota_target = max(
                                    $this->max_santri_kuota_target,
                                    1100
                                );
                            }
                            $this->hitungHarga();
                        }),

                    TextInput::make('max_santri_kuota_target')
                        ->label('Kuota Santri')
                        ->numeric()
                        ->minValue(1001)
                        ->step(100)
                        ->helperText('Minimum 1.001 untuk paket Maju, kelipatan 100.')
                        ->visible(fn () => $this->paket_target === 'maju')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (?string $state) {
                            $this->max_santri_kuota_target = (int) ($state ?? 1100);
                            $this->hitungHarga();
                        }),
                ]),

            Section::make('Pilih Durasi')
                ->schema([
                    Select::make('durasi_bulan')
                        ->label('Durasi Langganan')
                        ->options(DurasiLangganan::options())
                        ->required()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function (int $state) {
                            $this->durasi_bulan = $state;
                            $this->hitungHarga();
                            $this->terapkanKupon();
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
                        }),
                ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->footer([
                    Actions::make([
                        Action::make('prosesPembayaran')
                            ->label('Lakukan Pembayaran')
                            ->icon(Heroicon::OutlinedCreditCard)
                            ->color('primary')
                            ->action('prosesPembayaran'),
                    ])->key('form-actions'),
                ]),
        ]);
    }

    public function hitungHarga(): void
    {
        $calculator = app(BillingCalculatorService::class);
        $hasil      = $calculator->hitungUntukTarget($this->paket_target, $this->max_santri_kuota_target);

        $durasi                           = DurasiLangganan::from($this->durasi_bulan);
        $this->harga_per_bulan            = $hasil['total_biaya'];
        $this->bonus_bulan                = $durasi->bonusBulan();
        $this->bulan_bayar                = $durasi->bulanBayar();
        $this->harga_total_sebelum_diskon = $this->harga_per_bulan * $this->bulan_bayar;

        $this->terapkanKupon();
    }

    public function terapkanKupon(): void
    {
        if (empty($this->kode_kupon)) {
            $this->diskon_nominal = 0;
            $this->kupon_pesan    = null;
            $this->kupon_valid    = false;
            $this->harga_total    = $this->harga_total_sebelum_diskon;
            return;
        }

        $kupon = Kupon::where('kode', strtoupper($this->kode_kupon))->first();

        if (! $kupon || ! $kupon->isValid($this->durasi_bulan)) {
            $this->diskon_nominal = 0;
            $this->kupon_pesan    = 'Kode kupon tidak valid atau sudah kadaluwarsa.';
            $this->kupon_valid    = false;
            $this->harga_total    = $this->harga_total_sebelum_diskon;
            return;
        }

        $this->diskon_nominal = $kupon->hitungDiskon($this->harga_total_sebelum_diskon);
        $this->harga_total    = max(0, $this->harga_total_sebelum_diskon - $this->diskon_nominal);
        $this->kupon_valid    = true;
        $this->kupon_pesan    = 'Kupon berhasil diterapkan!';
    }

    public function prosesPembayaran(): void
    {
        $this->form->getState();

        $pesantren = Auth::user()->pesantren;

        $service = app(UpgradeOrderService::class);
        $result  = $service->createOrder(
            pesantren:       $pesantren,
            paketTarget:     $this->paket_target,
            durasibulan:     $this->durasi_bulan,
            maxSantriKuota:  $this->max_santri_kuota_target,
            kodeKupon:       $this->kode_kupon ?: null,
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
        return 'Rp ' . number_format($nilai, 0, ',', '.');
    }
}
