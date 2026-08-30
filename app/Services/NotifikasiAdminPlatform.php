<?php

namespace App\Services;

use App\Filament\Resources\DemoRequests\DemoRequestResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Jobs\KirimNotifikasiWhatsapp;
use App\Models\DemoRequest;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\PlatformContactSetting;
use App\Models\WhatsAppMessageTemplate;
use App\Models\WhatsAppSetting;

/**
 * Alert internal ke nomor WhatsApp pemilik platform saat ada lead demo baru atau
 * pesanan upgrade masuk.
 *
 * Kategorinya BERBEDA dari empat pengecualian sempit "WA selalu manual" (PRD §12):
 * penerimanya bukan pelanggan melainkan satu nomor tetap milik pemilik platform
 * sendiri, jadi volumenya beberapa pesan per hari ke satu tujuan. Karena itu ia
 * punya kill-switch sendiri yang DEFAULT MATI — menyalakannya keputusan sadar
 * Super Admin, bukan efek samping deploy.
 */
class NotifikasiAdminPlatform
{
    public function demoBaru(DemoRequest $demoRequest): void
    {
        $this->kirim('notif_admin_demo_baru', self::DEFAULT_DEMO_BARU, [
            '{nama_pesantren}' => $demoRequest->nama_pesantren,
            '{nama_kontak}' => $demoRequest->nama_kontak,
            '{no_hp}' => $demoRequest->no_hp ?: '-',
            '{kota}' => $demoRequest->kota ?: '-',
            '{jumlah_santri}' => $demoRequest->jumlah_santri ?: '-',
            '{link_admin}' => DemoRequestResource::getUrl('view', ['record' => $demoRequest]),
        ]);
    }

    public function orderBaru(Order $order, Invoice $invoice): void
    {
        $this->kirim('notif_admin_order_baru', self::DEFAULT_ORDER_BARU, $this->placeholderOrder($order, $invoice));
    }

    public function buktiTransferMasuk(Order $order, Invoice $invoice): void
    {
        $this->kirim('notif_admin_order_bukti', self::DEFAULT_ORDER_BUKTI, $this->placeholderOrder($order, $invoice));
    }

    /**
     * @return array<string, string>
     */
    private function placeholderOrder(Order $order, Invoice $invoice): array
    {
        $order->loadMissing('pesantren');

        return [
            '{nama_pesantren}' => $order->pesantren?->nama_pesantren ?? '-',
            '{nomor_order}' => $order->nomor_order,
            '{nomor_invoice}' => $invoice->nomor_invoice,
            '{paket}' => $order->paket_target->label(),
            '{durasi_bulan}' => (string) $order->durasi_total_bulan,
            '{total}' => $order->formatted_harga,
            '{link_admin}' => OrderResource::getUrl('view', ['record' => $order]),
        ];
    }

    /**
     * @param  array<string, string>  $placeholders
     */
    private function kirim(string $templateKey, string $defaultTemplate, array $placeholders): void
    {
        // Argumen kedua `false` WAJIB eksplisit: WhatsAppSetting::get() default-nya
        // true, jadi tanpa ini fitur menyala sendiri di DB yang barisnya belum ada.
        if (! WhatsAppSetting::get('notif_admin_platform_enabled', false)) {
            return;
        }

        $nomor = PlatformContactSetting::adminWhatsapp();

        if (blank($nomor)) {
            return;
        }

        KirimNotifikasiWhatsapp::dispatch(
            $nomor,
            strtr(WhatsAppMessageTemplate::get($templateKey, $defaultTemplate), $placeholders),
        );
    }

    public const DEFAULT_DEMO_BARU = <<<'TEXT'
    🔔 Lead demo baru

    Pesantren : {nama_pesantren}
    Kontak    : {nama_kontak}
    No. HP    : {no_hp}
    Kota      : {kota}
    Santri    : {jumlah_santri}

    Hubungi dalam 2 hari kerja:
    {link_admin}
    TEXT;

    public const DEFAULT_ORDER_BARU = <<<'TEXT'
    🛒 Pesanan upgrade baru

    Pesantren : {nama_pesantren}
    Order     : {nomor_order}
    Paket     : {paket}
    Durasi    : {durasi_bulan} bulan
    Total     : {total}

    Status: menunggu pembayaran.
    {link_admin}
    TEXT;

    public const DEFAULT_ORDER_BUKTI = <<<'TEXT'
    💳 Bukti transfer masuk — perlu konfirmasi

    Pesantren : {nama_pesantren}
    Order     : {nomor_order}
    Invoice   : {nomor_invoice}
    Paket     : {paket}
    Durasi    : {durasi_bulan} bulan
    Total     : {total}

    Konfirmasi dalam 1 hari kerja:
    {link_admin}
    TEXT;
}
