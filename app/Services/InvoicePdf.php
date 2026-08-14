<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\PlatformBankAccount;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;

/**
 * Perakitan PDF invoice, dipakai bersama halaman invoice dan lampiran email.
 *
 * Diekstrak supaya keduanya membaca sumber yang sama — pola yang sudah dipakai
 * `App\Services\Rapor\*` sejak v4.19. Sebelumnya perakitannya hanya ada inline di
 * OrderInvoicePage, sehingga lampiran email berisiko menjadi versi kedua yang
 * pelan-pelan menyimpang dari yang diunduh pesantren.
 */
class InvoicePdf
{
    public function untuk(Order $order, Invoice $invoice): DomPdf
    {
        return Pdf::loadView('filament.pdf.invoice', [
            'order' => $order,
            'invoice' => $invoice,
            'pesantren' => $order->pesantren,
            'bankAccounts' => PlatformBankAccount::where('aktif', true)
                ->orderBy('urutan')
                ->get(),
        ])->setPaper('A4', 'portrait');
    }
}
