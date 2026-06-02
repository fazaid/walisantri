<x-filament-panels::page>

<div style="display: grid; grid-template-columns: 1fr; gap: 1.5rem; align-items: start;">

    @php $isWide = true; @endphp

    {{-- Outer wrapper: form (kiri) + ringkasan (kanan) --}}
    <div style="display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); gap: 1.5rem; align-items: start;">

        {{-- KIRI: Form pilih paket --}}
        <div>
            {{ $this->content }}
        </div>

        {{-- KANAN: Ringkasan biaya (sticky) --}}
        <div style="position: sticky; top: 5rem;">

            {{-- Card total biaya --}}
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.07);">

                {{-- Header --}}
                <div style="padding: 1rem 1.25rem; border-bottom: 1px solid #f3f4f6;">
                    <h3 style="font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0;">Ringkasan Biaya</h3>
                </div>

                {{-- Body --}}
                <div style="padding: 1.25rem;">

                    {{-- Paket badge --}}
                    @if($this->paket_target)
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #f3f4f6;">
                        <span style="font-size: 0.75rem; color: #6b7280;">Paket</span>
                        <span style="background: #f0fdf4; color: #166534; font-size: 0.75rem; font-weight: 600; padding: 0.2rem 0.6rem; border-radius: 9999px; text-transform: capitalize;">
                            {{ $this->paket_target }}
                        </span>
                    </div>
                    @endif

                    {{-- Rincian baris --}}
                    <div style="display: flex; flex-direction: column; gap: 0.625rem; font-size: 0.8125rem;">

                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #6b7280;">Harga / bulan</span>
                            <span style="font-weight: 500; color: #111827;">{{ $this->formatRupiah($this->harga_per_bulan) }}</span>
                        </div>

                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #6b7280;">Durasi bayar</span>
                            <span style="font-weight: 500; color: #111827;">{{ $this->bulan_bayar }} bulan</span>
                        </div>

                        @if($this->bonus_bulan > 0)
                        <div style="display: flex; justify-content: space-between; color: #059669;">
                            <span>Gratis</span>
                            <span style="font-weight: 500;">+{{ $this->bonus_bulan }} bulan</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; color: #374151;">
                            <span style="color: #6b7280;">Total aktif</span>
                            <span style="font-weight: 600;">{{ $this->durasi_bulan }} bulan</span>
                        </div>
                        @endif

                        <div style="display: flex; justify-content: space-between; padding-top: 0.625rem; border-top: 1px solid #f3f4f6; margin-top: 0.25rem;">
                            <span style="color: #6b7280;">Subtotal</span>
                            <span style="font-weight: 500; color: #111827;">{{ $this->formatRupiah($this->harga_total_sebelum_diskon) }}</span>
                        </div>

                        @if($this->diskon_nominal > 0)
                        <div style="display: flex; justify-content: space-between; color: #059669;">
                            <span>Diskon kupon</span>
                            <span style="font-weight: 500;">− {{ $this->formatRupiah($this->diskon_nominal) }}</span>
                        </div>
                        @endif

                    </div>

                    {{-- Pesan kupon --}}
                    @if($this->kupon_pesan)
                    <div style="margin-top: 0.75rem; padding: 0.5rem 0.75rem; border-radius: 0.5rem; font-size: 0.75rem;
                        {{ $this->kupon_valid
                            ? 'background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;'
                            : 'background: #fff1f2; color: #9f1239; border: 1px solid #fecdd3;' }}">
                        {{ $this->kupon_valid ? '✓' : '✗' }} {{ $this->kupon_pesan }}
                    </div>
                    @endif

                    {{-- Total --}}
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 2px solid #f3f4f6; display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.875rem; font-weight: 600; color: #374151;">Total Bayar</span>
                        <span style="font-size: 1.25rem; font-weight: 700; color: #0d9488;">
                            {{ $this->formatRupiah($this->harga_total) }}
                        </span>
                    </div>

                    {{-- Badge diskon tahunan --}}
                    @if($this->bonus_bulan > 0)
                    <div style="margin-top: 0.875rem; padding: 0.5rem 0.75rem; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 0.5rem; font-size: 0.75rem; color: #065f46; display: flex; align-items: center; gap: 0.375rem;">
                        <span>🎉</span>
                        <span>{{ config('billing.diskon_tahunan.label') }}</span>
                    </div>
                    @endif

                </div>
            </div>

            {{-- Info pembayaran --}}
            <div style="margin-top: 0.75rem; padding: 0.875rem 1rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.75rem; font-size: 0.75rem; color: #64748b; line-height: 1.5;">
                <div style="font-weight: 600; color: #475569; margin-bottom: 0.25rem;">📋 Cara pembayaran</div>
                Setelah klik tombol di bawah, Anda akan mendapat nomor invoice dan instruksi transfer bank.
            </div>

        </div>
        {{-- /KANAN --}}

    </div>

</div>

</x-filament-panels::page>
