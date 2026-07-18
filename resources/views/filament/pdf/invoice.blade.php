<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page {
            margin: 2.5cm 2cm;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            line-height: 1.5;
            /* @page margin kadang tidak konsisten diterapkan tergantung viewer PDF —
               padding di body sebagai jaring pengaman supaya konten tetap berjarak
               dari tepi kertas. */
            padding: 0.5cm 0.3cm;
        }

        .letterhead {
            display: table;
            width: 100%;
            margin-bottom: 16px;
            padding-bottom: 14px;
            border-bottom: 2px solid #0f766e;
        }
        .letterhead .brand { display: table-cell; vertical-align: top; width: 55%; }
        .letterhead .brand-logo { height: 32px; margin-bottom: 4px; }
        .letterhead .brand-name { font-size: 20px; font-weight: bold; color: #0f766e; }
        .letterhead .brand-tagline { font-size: 9px; color: #6b7280; margin-top: 2px; }
        .letterhead .doc-info { display: table-cell; vertical-align: top; width: 45%; text-align: right; }
        .letterhead .doc-title { font-size: 22px; font-weight: bold; color: #111827; letter-spacing: 1px; }
        .letterhead .doc-meta { font-size: 10px; color: #4b5563; margin-top: 4px; }

        .status-stamp {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 16px;
            letter-spacing: 0.5px;
        }
        .stamp-confirmed { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .stamp-awaiting  { background: #fef9c3; color: #854d0e; border: 1px solid #fde047; }
        .stamp-pending   { background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }
        .stamp-rejected  { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

        .bill-to { margin-bottom: 16px; }
        .bill-to .label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af; margin-bottom: 3px; }
        .bill-to .name { font-size: 13px; font-weight: bold; color: #111827; }
        .bill-to .detail { font-size: 10px; color: #4b5563; margin-top: 2px; }

        .section-title {
            background: #f0fdfa;
            border-left: 3px solid #0d9488;
            padding: 5px 10px;
            font-size: 11px;
            font-weight: bold;
            color: #0f766e;
            margin: 16px 0 8px;
        }

        .period-highlight {
            background: #f0fdfa;
            border: 1px solid #99f6e4;
            border-radius: 6px;
            padding: 12px 16px;
            margin-bottom: 4px;
            text-align: center;
        }
        .period-highlight .label { font-size: 9px; text-transform: uppercase; color: #0f766e; letter-spacing: 0.5px; }
        .period-highlight .dates { font-size: 15px; font-weight: bold; color: #111827; margin-top: 4px; }

        table.detail-table { width: 100%; margin-bottom: 8px; }
        table.detail-table td { padding: 4px 0; font-size: 10.5px; vertical-align: top; }
        table.detail-table td:first-child { color: #6b7280; width: 160px; }

        table.rincian-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.rincian-table th { background: #0f766e; color: #fff; padding: 6px 10px; text-align: left; font-size: 10px; }
        table.rincian-table td { padding: 6px 10px; border-bottom: 1px solid #e5e7eb; font-size: 10.5px; }
        table.rincian-table .amount { text-align: right; }
        table.rincian-table .diskon-row td { color: #059669; }
        table.rincian-table .total-row td {
            border-top: 2px solid #0f766e;
            border-bottom: none;
            font-weight: bold;
            font-size: 12px;
            color: #0f766e;
            padding-top: 10px;
        }

        .payment-note {
            font-size: 10px;
            color: #4b5563;
            margin-bottom: 8px;
        }

        table.bank-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.bank-table td {
            padding: 8px 10px;
            border: 1px solid #e5e7eb;
            font-size: 10.5px;
            vertical-align: top;
        }
        table.bank-table .bank-name { font-weight: bold; color: #0f766e; width: 32%; }
        table.bank-table .bank-nomor { font-weight: bold; letter-spacing: 0.5px; }
        table.bank-table .bank-atasnama { color: #6b7280; font-size: 9.5px; margin-top: 2px; }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 4px;
        }
    </style>
</head>
<body>

<div class="footer">
    Dicetak via Walisantri.com — {{ now()->translatedFormat('d F Y, H:i') }} WIB
</div>

<div class="letterhead">
    <div class="brand">
        <img src="{{ \App\Models\PlatformBrandingSetting::logoPath() }}" alt="Walisantri.com" class="brand-logo">
        <div class="brand-name">Walisantri.com</div>
        <div class="brand-tagline">Platform Manajemen Pesantren Terpadu</div>
    </div>
    <div class="doc-info">
        <div class="doc-title">INVOICE</div>
        <div class="doc-meta">
            No. {{ $invoice->nomor_invoice }}<br>
            {{ $invoice->created_at->translatedFormat('d F Y') }}
        </div>
    </div>
</div>

@php
    $stampClass = match($order->status->value) {
        'confirmed' => 'stamp-confirmed',
        'awaiting_confirmation' => 'stamp-awaiting',
        'rejected' => 'stamp-rejected',
        default => 'stamp-pending',
    };
    $stampLabel = match($order->status->value) {
        'confirmed' => '✓ LUNAS — DIKONFIRMASI',
        'awaiting_confirmation' => 'MENUNGGU KONFIRMASI PEMBAYARAN',
        'rejected' => '✗ DITOLAK',
        default => 'BELUM DIBAYAR',
    };
@endphp
<div class="status-stamp {{ $stampClass }}">{{ $stampLabel }}</div>

<div class="bill-to">
    <div class="label">Ditagihkan Kepada</div>
    <div class="name">{{ $pesantren->nama_pesantren }}</div>
    @if($pesantren->profil['alamat'] ?? null)
    <div class="detail">{{ $pesantren->profil['alamat'] }}</div>
    @endif
    @if($pesantren->profil['telepon'] ?? null)
    <div class="detail">Telp: {{ $pesantren->profil['telepon'] }}</div>
    @endif
</div>

<div class="section-title">Detail Langganan</div>
<table class="detail-table">
    <tr>
        <td>Nomor Order</td>
        <td>: {{ $order->nomor_order }}</td>
    </tr>
    <tr>
        <td>Paket</td>
        <td>: {{ $order->paket_target->label() }}</td>
    </tr>
    <tr>
        <td>Kuota Santri</td>
        <td>: {{ number_format($order->max_santri_kuota_target, 0, ',', '.') }} santri</td>
    </tr>
    <tr>
        <td>Durasi</td>
        <td>
            @php $bulanBayar = $order->durasi_bulan - $order->bonus_bulan; @endphp
            : {{ $bulanBayar }} bulan dibayar
            @if($order->bonus_bulan > 0)
                + {{ $order->bonus_bulan }} bulan bonus (total {{ $order->durasi_total_bulan }} bulan aktif)
            @endif
        </td>
    </tr>
</table>

<div class="period-highlight">
    <div class="label">Periode Langganan</div>
    <div class="dates">
        {{ $order->periodeMulai()->translatedFormat('d F Y') }} &mdash; {{ $order->periodeSelesai()->translatedFormat('d F Y') }}
    </div>
</div>

<div class="section-title">Rincian Biaya</div>
<table class="rincian-table">
    <tr>
        <th>Keterangan</th>
        <th class="amount">Jumlah</th>
    </tr>
    <tr>
        <td>Harga per bulan ({{ $order->paket_target->label() }})</td>
        <td class="amount">Rp {{ number_format($order->harga_per_bulan, 0, ',', '.') }}</td>
    </tr>
    <tr>
        <td>Subtotal ({{ $bulanBayar }} bulan dibayar)</td>
        <td class="amount">Rp {{ number_format($order->harga_total_sebelum_diskon, 0, ',', '.') }}</td>
    </tr>
    @if($order->diskon_nominal > 0)
    <tr class="diskon-row">
        <td>Diskon Kupon{{ $order->kode_kupon_snapshot ? " ({$order->kode_kupon_snapshot})" : '' }}</td>
        <td class="amount">&minus; Rp {{ number_format($order->diskon_nominal, 0, ',', '.') }}</td>
    </tr>
    @endif
    <tr class="total-row">
        <td>Total</td>
        <td class="amount">{{ $order->formatted_harga }}</td>
    </tr>
</table>

@if(($bankAccounts ?? collect())->isNotEmpty())
<div class="section-title">Informasi Rekening Pembayaran</div>
<p class="payment-note">
    @if($order->status->value === 'confirmed')
        Pembayaran untuk invoice ini telah dikonfirmasi. Rekening berikut dicatat sebagai referensi tujuan transfer.
    @else
        Silakan transfer sesuai nominal total ke salah satu rekening berikut, lalu unggah bukti transfer di halaman invoice.
    @endif
</p>
<table class="bank-table">
    @foreach($bankAccounts as $bank)
    <tr>
        <td class="bank-name">{{ $bank->bank }}</td>
        <td>
            <div class="bank-nomor">{{ $bank->nomor_rekening }}</div>
            <div class="bank-atasnama">a.n. {{ $bank->atas_nama }}</div>
        </td>
    </tr>
    @endforeach
</table>
@endif

</body>
</html>
