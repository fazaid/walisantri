<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\PlatformBankAccount;
use App\Services\UpgradeOrderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use UnitEnum;

class OrderInvoicePage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;
    protected static ?string $navigationLabel              = 'Invoice';
    protected static ?string $title                        = 'Invoice Pembayaran';
    protected static bool $shouldRegisterNavigation        = false;

    protected string $view = 'filament.pages.order-invoice-page';

    public Order   $order;
    public Invoice $invoice;
    public array|null $bukti_transfer = [];

    public static function canAccess(): bool
    {
        return Auth::user()?->role === 'admin_pesantren';
    }

    public function mount(): void
    {
        $orderId = (int) request()->query('order', 0);

        if (! $orderId) {
            $this->redirect(BillingPage::getUrl());
            return;
        }

        $this->order = Order::with(['invoice', 'pesantren'])->findOrFail($orderId);

        abort_unless(
            $this->order->pesantren_id === Auth::user()->pesantren_id,
            403,
            'Akses tidak diizinkan.'
        );

        $this->invoice = $this->order->invoice;

        $this->form->fill();
    }

    public function getBankAccounts(): Collection
    {
        return PlatformBankAccount::where('aktif', true)
            ->orderBy('urutan')
            ->get();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('bukti_transfer')
                ->label('Upload Bukti Transfer')
                ->disk('local')
                ->helperText('Format: JPG, PNG, atau PDF. Maks. 5 MB.')
                ->visible(fn () => $this->order->isPendingPayment())
                ->storeFiles(false)
                ->fetchFileInformation(false),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->footer([
                    Actions::make([
                        Action::make('uploadBukti')
                            ->label('Kirim Bukti Transfer')
                            ->icon(Heroicon::OutlinedArrowUpTray)
                            ->color('primary')
                            ->visible(fn () => $this->order->isPendingPayment())
                            ->action('uploadBukti'),
                    ])->key('form-actions'),
                ]),
        ]);
    }

    public function uploadBukti(): void
    {
        $data = $this->form->getState();
        $raw  = $data['bukti_transfer'] ?? null;
        $file = $raw instanceof TemporaryUploadedFile ? $raw : ($raw[0] ?? null);

        if (! $file) {
            Notification::make()
                ->title('File belum dipilih')
                ->body('Silakan pilih file bukti transfer terlebih dahulu.')
                ->danger()
                ->send();

            return;
        }

        // Livewire serializes TemporaryUploadedFile as just the basename (loses livewire-tmp/ prefix).
        // getFilename() always returns the basename regardless, so we reconstruct the correct path.
        if ($file instanceof TemporaryUploadedFile) {
            $diskPath   = 'livewire-tmp/' . $file->getFilename();
            $sourcePath = Storage::disk('local')->path($diskPath);
        } else {
            $diskPath   = $file;
            $sourcePath = Storage::disk('local')->path($diskPath);
        }

        if (! file_exists($sourcePath)) {
            Notification::make()
                ->title('File tidak ditemukan')
                ->body('Silakan upload ulang bukti transfer.')
                ->danger()
                ->send();

            $this->reset('bukti_transfer');

            return;
        }

        $mime = mime_content_type($sourcePath) ?: 'application/octet-stream';

        if (! in_array($mime, ['image/jpeg', 'image/png', 'application/pdf'])) {
            Notification::make()
                ->title('Format file tidak valid')
                ->body('Gunakan format JPG, PNG, atau PDF.')
                ->danger()
                ->send();

            return;
        }

        if (filesize($sourcePath) > 5 * 1024 * 1024) {
            Notification::make()
                ->title('Ukuran file terlalu besar')
                ->body('Maksimal 5 MB.')
                ->danger()
                ->send();

            return;
        }

        $ext = match ($mime) {
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'application/pdf' => 'pdf',
        };

        $dest     = "bukti-transfer/{$this->order->id}/bukti.{$ext}";
        $fullPath = Storage::disk('local')->path($dest);

        Storage::disk('local')->move($diskPath, $dest);

        if (! file_exists($fullPath)) {
            Notification::make()
                ->title('Gagal menyimpan file')
                ->body('Silakan coba upload ulang.')
                ->danger()
                ->send();

            return;
        }

        $uploadedFile = new \Illuminate\Http\UploadedFile($fullPath, "bukti.{$ext}", $mime, null, true);

        app(UpgradeOrderService::class)->uploadBuktiTransfer($this->invoice, $uploadedFile);

        $this->order->refresh();
        $this->invoice->refresh();

        Notification::make()
            ->title('Bukti transfer berhasil dikirim!')
            ->body('Tim kami akan memverifikasi pembayaran Anda dalam 1×24 jam.')
            ->success()
            ->send();

        $this->redirect(static::getUrl(['order' => $this->order->id]));
    }

    public function formatRupiah(int $nilai): string
    {
        return 'Rp ' . number_format($nilai, 0, ',', '.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('unduhInvoicePdf')
                ->label('Unduh Invoice PDF')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(function () {
                    $pdf = Pdf::loadView('filament.pdf.invoice', [
                        'order' => $this->order,
                        'invoice' => $this->invoice,
                        'pesantren' => $this->order->pesantren,
                        'bankAccounts' => $this->getBankAccounts(),
                    ])->setPaper('A4', 'portrait');

                    return response()->streamDownload(
                        fn () => print ($pdf->output()),
                        "Invoice-{$this->invoice->nomor_invoice}.pdf",
                        ['Content-Type' => 'application/pdf'],
                    );
                }),
        ];
    }
}
