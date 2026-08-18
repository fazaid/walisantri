<?php

namespace App\Filament\Pages;

use App\Enums\NavigationGroup;
use App\Enums\PaketLangganan;
use App\Enums\StatusBerlangganan;
use App\Models\Order;
use App\Models\Pesantren;
use App\Services\BillingCalculatorService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class BillingPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    // Sengaja tidak di dalam Cluster PengaturanPesantren: label "Billing" bersebelahan
    // dengan menu "Keuangan" bikin rancu (Keuangan = uang santri ke pesantren,
    // Langganan = pesantren bayar platform). Di luar cluster, slug-nya kembali jadi
    // "admin/billing-page" — persis URL yang dipakai notifikasi WA & email expired.
    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Manajemen;

    protected static ?string $navigationLabel = 'Langganan';

    protected static ?string $title = 'Informasi Langganan';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.billing-page';

    public static function canAccess(): bool
    {
        return Auth::user()?->role === 'admin_pesantren';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upgrade')
                ->label('Upgrade / Perpanjang Paket')
                ->icon(Heroicon::OutlinedArrowUpCircle)
                ->url(UpgradePage::getUrl())
                ->color('primary'),

            $this->gantiPaketTrialAction(),
        ];
    }

    /**
     * Ganti paket selama masa trial — tanpa order, tanpa pembayaran.
     *
     * Pendaftar memilih paketnya di /register (§4.1) berdasarkan perkiraan, dan
     * perkiraan itu bisa meleset begitu data santri sungguhan masuk. Menyuruhnya
     * mendaftar ulang berarti membuang tenant yang sudah diisi.
     *
     * `expired_at` sengaja TIDAK disentuh: masa trial berjalan dengan sisa hari yang
     * ada. Kalau ia direset, mengganti paket berulang kali jadi trial tanpa batas.
     * Perubahan paketnya sendiri sudah dicatat PesantrenObserver (pesantren.paket_changed).
     */
    protected function gantiPaketTrialAction(): Action
    {
        return Action::make('gantiPaketTrial')
            ->label('Ganti Paket Trial')
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->color('gray')
            ->visible(fn (): bool => $this->sedangTrial())
            ->modalHeading('Ganti Paket Trial')
            ->modalDescription('Kuota santri langsung mengikuti paket baru. Masa trial tidak direset — tanggal berakhirnya tetap sama.')
            ->modalSubmitActionLabel('Ganti Paket')
            ->schema([
                Select::make('paket')
                    ->label('Paket')
                    ->options(fn (): array => $this->opsiPaketTrial())
                    ->default(fn (): ?string => $this->getPesantren()?->paket_langganan)
                    ->required()
                    ->native(false)
                    ->helperText('Paket Maju tidak ada di daftar ini — kuotanya disesuaikan lewat percakapan dengan tim kami.'),
            ])
            ->action(function (array $data): void {
                $this->gantiPaketTrial($data['paket']);
            });
    }

    private function sedangTrial(): bool
    {
        return $this->getPesantren()?->status_berlangganan === StatusBerlangganan::Trial->value;
    }

    /**
     * @return array<string, string>
     */
    private function opsiPaketTrial(): array
    {
        $santriAktif = $this->getPesantren()?->jumlahSantriAktif() ?? 0;

        return collect(PaketLangganan::pilihanMandiri())
            ->mapWithKeys(function (PaketLangganan $paket) use ($santriAktif) {
                $kuota = $this->kuotaPaket($paket);
                $label = $paket->label().' — sampai '.number_format($kuota, 0, ',', '.').' santri';

                // Ditandai, bukan disembunyikan: opsi yang hilang tanpa penjelasan
                // membuat admin mengira paketnya tidak ada, bukan kuotanya kurang.
                if ($kuota < $santriAktif) {
                    $label .= ' (kuota tidak cukup)';
                }

                return [$paket->value => $label];
            })
            ->all();
    }

    private function kuotaPaket(PaketLangganan $paket): int
    {
        return app(BillingCalculatorService::class)
            ->hitungUntukTarget($paket->value, 0)['kuota_maksimal'];
    }

    private function gantiPaketTrial(string $nilaiPaket): void
    {
        $pesantren = $this->getPesantren();
        $paket = PaketLangganan::tryFrom($nilaiPaket);

        // Ketiganya diperiksa ulang di sini, bukan hanya di ->visible()/->options():
        // aksi Livewire tetap bisa dipanggil langsung dari klien.
        if (! $pesantren instanceof Pesantren || ! $this->sedangTrial() || ! $paket?->bisaDipilihSendiri()) {
            $this->gagal('Paket ini tidak bisa dipilih sendiri.');

            return;
        }

        $kuota = $this->kuotaPaket($paket);

        // Pagar yang sama dengan UpgradePage::kuotaKurang(): SantriObserver hanya
        // menahan penambahan santri, tidak membereskan kelebihan yang terlanjur ada.
        if ($kuota < $pesantren->jumlahSantriAktif()) {
            $this->gagal(
                'Pesantren Anda punya '.number_format($pesantren->jumlahSantriAktif(), 0, ',', '.')
                .' santri aktif, melebihi kuota '.number_format($kuota, 0, ',', '.').' pada paket ini.'
            );

            return;
        }

        $pesantren->update([
            'paket_langganan' => $paket->value,
            'max_santri_kuota' => $kuota,
        ]);

        Notification::make()
            ->title('Paket trial diganti')
            ->body('Paket '.$paket->label().' aktif dengan kuota '.number_format($kuota, 0, ',', '.')
                .' santri. Masa trial tetap berakhir pada tanggal yang sama.')
            ->success()
            ->send();
    }

    private function gagal(string $pesan): void
    {
        Notification::make()
            ->title('Paket tidak bisa diganti')
            ->body($pesan)
            ->danger()
            ->send();
    }

    public function getPesantren()
    {
        return Auth::user()?->pesantren;
    }

    public function getActiveOrder(): ?Order
    {
        return $this->getPesantren()?->activeOrder;
    }
}
