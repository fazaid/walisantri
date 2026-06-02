<x-filament-panels::page>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

    {{-- Kiri: Form pilih paket --}}
    <div class="lg:col-span-2">
        {{ $this->content }}
    </div>

    {{-- Kanan: Ringkasan biaya --}}
    <div class="lg:col-span-1">
        <x-filament::section>
            <x-slot name="heading">Ringkasan Biaya</x-slot>

            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Paket</dt>
                    <dd class="font-medium capitalize">{{ $this->paket_target ?: '—' }}</dd>
                </div>

                <div class="flex justify-between">
                    <dt class="text-gray-500">Harga / bulan</dt>
                    <dd class="font-medium">{{ $this->formatRupiah($this->harga_per_bulan) }}</dd>
                </div>

                <div class="flex justify-between">
                    <dt class="text-gray-500">Durasi</dt>
                    <dd class="font-medium">{{ $this->durasi_bulan }} bulan</dd>
                </div>

                @if($this->bonus_bulan > 0)
                <div class="flex justify-between text-emerald-600">
                    <dt>Bonus tahunan</dt>
                    <dd class="font-medium">+{{ $this->bonus_bulan }} bulan gratis</dd>
                </div>
                <div class="flex justify-between text-emerald-600">
                    <dt>Total aktif</dt>
                    <dd class="font-medium">{{ $this->durasi_bulan + $this->bonus_bulan }} bulan</dd>
                </div>
                @endif

                <div class="border-t border-gray-100 pt-3 flex justify-between">
                    <dt class="text-gray-500">Subtotal</dt>
                    <dd class="font-medium">{{ $this->formatRupiah($this->harga_total_sebelum_diskon) }}</dd>
                </div>

                @if($this->diskon_nominal > 0)
                <div class="flex justify-between text-emerald-600">
                    <dt>Diskon kupon</dt>
                    <dd class="font-medium">− {{ $this->formatRupiah($this->diskon_nominal) }}</dd>
                </div>
                @endif

                @if($this->kupon_pesan)
                <div class="rounded-lg px-3 py-2 text-xs
                    {{ $this->kupon_valid ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                    {{ $this->kupon_pesan }}
                </div>
                @endif

                <div class="border-t-2 border-gray-200 pt-3 flex justify-between text-base">
                    <dt class="font-bold text-gray-800">Total Bayar</dt>
                    <dd class="font-bold text-primary-600 text-lg">
                        {{ $this->formatRupiah($this->harga_total) }}
                    </dd>
                </div>
            </dl>

            @if($this->bonus_bulan > 0)
            <div class="mt-4 rounded-lg bg-emerald-50 px-3 py-2 text-xs text-emerald-700">
                🎉 {{ config('billing.diskon_tahunan.label') }}
            </div>
            @endif
        </x-filament::section>
    </div>

</div>

</x-filament-panels::page>
