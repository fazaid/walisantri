<x-filament-panels::page>

<div class="space-y-6">

    {{-- Header status --}}
    <div class="flex items-center gap-3">
        <x-filament::badge :color="$this->order->status->color()" size="lg">
            {{ $this->order->status->label() }}
        </x-filament::badge>
        <span class="text-sm text-gray-500">{{ $this->invoice->nomor_invoice }}</span>
    </div>

    @if($this->order->status->value === 'rejected' && $this->order->catatan_admin)
    <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
        <strong>Alasan penolakan:</strong> {{ $this->order->catatan_admin }}
    </div>
    @endif

    @if($this->order->status->value === 'confirmed')
    <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
        ✅ Pembayaran dikonfirmasi. Paket <strong>{{ ucfirst($this->order->paket_target->value) }}</strong>
        telah aktif hingga <strong>{{ $this->order->expired_at_baru?->translatedFormat('d F Y') ?? '—' }}</strong>.
    </div>
    @endif

    {{-- Detail Order --}}
    <x-filament::section>
        <x-slot name="heading">Detail Order</x-slot>

        <dl class="divide-y divide-gray-100">
            @foreach([
                'Nomor Order'    => $this->order->nomor_order,
                'Nomor Invoice'  => $this->invoice->nomor_invoice,
                'Paket'          => ucfirst($this->order->paket_target->value),
                'Kuota Santri'   => number_format($this->order->max_santri_kuota_target, 0, ',', '.') . ' santri',
                'Durasi'         => $this->order->durasi_bulan . ' bulan' .
                    ($this->order->bonus_bulan > 0
                        ? ' + ' . $this->order->bonus_bulan . ' bulan bonus (total ' . $this->order->durasi_total_bulan . ' bulan)'
                        : ''),
                'Dibuat'         => $this->order->created_at->translatedFormat('d F Y, H:i'),
            ] as $label => $value)
            <div class="flex justify-between items-center py-3">
                <dt class="text-sm text-gray-500">{{ $label }}</dt>
                <dd class="text-sm font-medium text-gray-800">{{ $value }}</dd>
            </div>
            @endforeach

            @if($this->order->kode_kupon_snapshot)
            <div class="flex justify-between items-center py-3">
                <dt class="text-sm text-gray-500">Kupon</dt>
                <dd class="text-sm font-medium text-emerald-600">{{ $this->order->kode_kupon_snapshot }}</dd>
            </div>
            <div class="flex justify-between items-center py-3">
                <dt class="text-sm text-gray-500">Diskon</dt>
                <dd class="text-sm font-medium text-emerald-600">
                    − {{ $this->formatRupiah($this->order->diskon_nominal) }}
                </dd>
            </div>
            @endif

            <div class="flex justify-between items-center py-3">
                <dt class="text-sm font-bold text-gray-800">Total Pembayaran</dt>
                <dd class="text-lg font-bold text-primary-600">
                    {{ $this->formatRupiah($this->order->harga_total) }}
                </dd>
            </div>
        </dl>
    </x-filament::section>

    {{-- Cara Pembayaran --}}
    @if($this->order->isPendingPayment() || $this->order->isAwaitingConfirmation())
    <x-filament::section>
        <x-slot name="heading">Cara Pembayaran</x-slot>
        <x-slot name="description">
            Transfer tepat sesuai nominal ke salah satu rekening berikut.
        </x-slot>

        <div class="space-y-3">
            @foreach(config('billing.bank_transfer', []) as $bank)
            <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">
                            {{ $bank['bank'] }}
                        </p>
                        <p class="text-xl font-bold font-mono tracking-widest text-gray-800">
                            {{ $bank['nomor'] }}
                        </p>
                        <p class="text-sm text-gray-500 mt-1">a.n. {{ $bank['atas_nama'] }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-4 rounded-lg bg-amber-50 border border-amber-200 px-3 py-3 text-sm text-amber-700">
            ⏰ Selesaikan pembayaran dalam
            <strong>{{ config('billing.order_expiry_hours', 24) }} jam</strong>
            sejak order dibuat, lalu upload bukti transfer di bawah.
        </div>
    </x-filament::section>
    @endif

    {{-- Upload Bukti --}}
    @if($this->order->isPendingPayment())
    <x-filament::section>
        <x-slot name="heading">Upload Bukti Transfer</x-slot>
        <x-slot name="description">Upload setelah melakukan transfer untuk mempercepat verifikasi.</x-slot>

        {{ $this->content }}
    </x-filament::section>
    @endif

    @if($this->order->isAwaitingConfirmation())
    <x-filament::section>
        <x-slot name="heading">Bukti Transfer</x-slot>

        @if($this->invoice->hasBuktiTransfer())
        <p class="text-sm text-gray-500 mb-3">
            Diunggah pada {{ $this->invoice->bukti_transfer_uploaded_at?->translatedFormat('d F Y, H:i') }}
        </p>
        <x-filament::badge color="success">✅ Bukti transfer telah dikirim</x-filament::badge>
        <p class="text-sm text-gray-500 mt-3">
            Tim kami akan memverifikasi dalam 1×24 jam kerja.
        </p>
        @endif
    </x-filament::section>
    @endif

</div>

</x-filament-panels::page>
