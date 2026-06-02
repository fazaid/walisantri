<?php

namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Models\Order;
use App\Services\UpgradeOrderService;
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
    public ?string $bukti_transfer = null;

    public static function canAccess(): bool
    {
        return Auth::user()?->role === 'admin_pesantren';
    }

    public function mount(int $order): void
    {
        $this->order = Order::with(['invoice', 'pesantren'])->findOrFail($order);

        abort_unless(
            $this->order->pesantren_id === Auth::user()->pesantren_id,
            403,
            'Akses tidak diizinkan.'
        );

        $this->invoice = $this->order->invoice;

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('bukti_transfer')
                ->label('Upload Bukti Transfer')
                ->disk('local')
                ->directory('bukti-transfer-tmp')
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'application/pdf'])
                ->maxSize(5120)
                ->helperText('Format: JPG, PNG, atau PDF. Maks. 5 MB.')
                ->visible(fn () => $this->order->isPendingPayment()),
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

        abort_unless(isset($data['bukti_transfer']), 422, 'File bukti transfer wajib diupload.');

        $service = app(UpgradeOrderService::class);

        // Pindahkan file dari tmp ke direktori permanen
        $tmpPath = $data['bukti_transfer'];
        $ext     = pathinfo($tmpPath, PATHINFO_EXTENSION);
        $dest    = "bukti-transfer/{$this->order->id}/bukti.{$ext}";

        \Illuminate\Support\Facades\Storage::disk('local')->move($tmpPath, $dest);

        // Buat UploadedFile semu dari path storage
        $fullPath    = storage_path("app/{$dest}");
        $uploadedFile = new \Illuminate\Http\UploadedFile(
            $fullPath,
            basename($fullPath),
            mime_content_type($fullPath),
            null,
            true
        );

        $service->uploadBuktiTransfer($this->invoice, $uploadedFile);

        // Refresh order
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
}
