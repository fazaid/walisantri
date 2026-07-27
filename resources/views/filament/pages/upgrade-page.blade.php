<x-filament-panels::page>

<style>
    .upgrade-layout {
        display: grid;
        grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
        gap: 1.5rem;
        align-items: start;
    }
    .upgrade-summary {
        position: sticky;
        top: 5rem;

        /* Palet kartu — memakai CSS variable milik Filament (--gray-*, --primary-*,
           --success-*, --danger-*) supaya ikut tema panel & punya varian dark mode. */
        --us-card-bg:          white;
        --us-card-shadow:      0 1px 3px rgba(0, 0, 0, .07);
        --us-border:           var(--gray-200);
        --us-border-soft:      var(--gray-100);
        --us-text:             var(--gray-950);
        --us-text-strong:      var(--gray-700);
        --us-text-muted:       var(--gray-500);
        --us-accent:           var(--primary-600);
        --us-positive:         var(--success-600);
        --us-success-bg:       var(--success-50);
        --us-success-border:   var(--success-200);
        --us-success-text:     var(--success-700);
        --us-danger-bg:        var(--danger-50);
        --us-danger-border:    var(--danger-200);
        --us-danger-text:      var(--danger-700);
        --us-info-bg:          var(--gray-50);
        --us-info-border:      var(--gray-200);
        --us-info-text:        var(--gray-500);
        --us-info-title:       var(--gray-600);
    }
    .dark .upgrade-summary {
        --us-card-bg:          var(--gray-900);
        --us-card-shadow:      none;
        --us-border:           var(--gray-800);
        --us-border-soft:      var(--gray-800);
        --us-text:             var(--gray-100);
        --us-text-strong:      var(--gray-200);
        --us-text-muted:       var(--gray-400);
        --us-accent:           var(--primary-400);
        --us-positive:         var(--success-400);
        --us-success-bg:       color-mix(in srgb, var(--success-400) 12%, transparent);
        --us-success-border:   color-mix(in srgb, var(--success-400) 30%, transparent);
        --us-success-text:     var(--success-300);
        --us-danger-bg:        color-mix(in srgb, var(--danger-400) 12%, transparent);
        --us-danger-border:    color-mix(in srgb, var(--danger-400) 30%, transparent);
        --us-danger-text:      var(--danger-300);
        --us-info-bg:          var(--gray-900);
        --us-info-border:      var(--gray-800);
        --us-info-text:        var(--gray-400);
        --us-info-title:       var(--gray-300);
    }
    /* Mobile: stack kolom & matikan sticky */
    @media (max-width: 1024px) {
        .upgrade-layout {
            grid-template-columns: minmax(0, 1fr);
        }
        .upgrade-summary {
            position: static;
            top: auto;
        }
    }
</style>

<div style="display: grid; grid-template-columns: minmax(0, 1fr); gap: 1.5rem; align-items: start;">

    {{-- Outer wrapper: form (kiri) + ringkasan (kanan) --}}
    <div class="upgrade-layout">

        {{-- KIRI: Form pilih paket --}}
        <div>
            {{ $this->content }}
        </div>

        {{-- KANAN: Ringkasan biaya (sticky) --}}
        <div class="upgrade-summary">

            {{-- Card total biaya --}}
            <div style="background: var(--us-card-bg); border: 1px solid var(--us-border); border-radius: 0.75rem; overflow: hidden; box-shadow: var(--us-card-shadow);">

                {{-- Header --}}
                <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--us-border-soft);">
                    <h3 style="font-size: 0.875rem; font-weight: 600; color: var(--us-text); margin: 0;">Ringkasan Biaya</h3>
                </div>

                {{-- Body --}}
                <div style="padding: 1.25rem;">

                    {{-- Paket badge --}}
                    @if($this->paket_target)
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--us-border-soft);">
                        <span style="font-size: 0.75rem; color: var(--us-text-muted);">Paket</span>
                        <span style="background: var(--us-success-bg); color: var(--us-success-text); font-size: 0.75rem; font-weight: 600; padding: 0.2rem 0.6rem; border-radius: 9999px; text-transform: capitalize;">
                            {{ $this->paket_target }}
                        </span>
                    </div>
                    @endif

                    {{-- Rincian baris --}}
                    <div style="display: flex; flex-direction: column; gap: 0.625rem; font-size: 0.8125rem;">

                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--us-text-muted);">Harga / bulan</span>
                            <span style="font-weight: 500; color: var(--us-text);">{{ $this->formatRupiah($this->harga_per_bulan) }}</span>
                        </div>

                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--us-text-muted);">Durasi bayar</span>
                            <span style="font-weight: 500; color: var(--us-text);">{{ $this->bulan_bayar }} bulan</span>
                        </div>

                        @if($this->bonus_bulan > 0)
                        <div style="display: flex; justify-content: space-between; color: var(--us-positive);">
                            <span>Gratis</span>
                            <span style="font-weight: 500;">+{{ $this->bonus_bulan }} bulan</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; color: var(--us-text-strong);">
                            <span style="color: var(--us-text-muted);">Total aktif</span>
                            <span style="font-weight: 600;">{{ $this->durasi_bulan }} bulan</span>
                        </div>
                        @endif

                        <div style="display: flex; justify-content: space-between; padding-top: 0.625rem; border-top: 1px solid var(--us-border-soft); margin-top: 0.25rem;">
                            <span style="color: var(--us-text-muted);">Subtotal</span>
                            <span style="font-weight: 500; color: var(--us-text);">{{ $this->formatRupiah($this->harga_total_sebelum_diskon) }}</span>
                        </div>

                        @if($this->diskon_nominal > 0)
                        <div style="display: flex; justify-content: space-between; color: var(--us-positive);">
                            <span>Diskon kupon @if($this->diskon_persen)({{ $this->diskon_persen }}%)@endif</span>
                            <span style="font-weight: 500;">− {{ $this->formatRupiah($this->diskon_nominal) }}</span>
                        </div>
                        @endif

                    </div>

                    {{-- Pesan kupon --}}
                    @if($this->kupon_pesan)
                    <div style="margin-top: 0.75rem; padding: 0.5rem 0.75rem; border-radius: 0.5rem; font-size: 0.75rem;
                        {{ $this->kupon_valid
                            ? 'background: var(--us-success-bg); color: var(--us-success-text); border: 1px solid var(--us-success-border);'
                            : 'background: var(--us-danger-bg); color: var(--us-danger-text); border: 1px solid var(--us-danger-border);' }}">
                        {{ $this->kupon_valid ? '✓' : '✗' }} {{ $this->kupon_pesan }}
                    </div>
                    @endif

                    {{-- Total --}}
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 2px solid var(--us-border-soft); display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.875rem; font-weight: 600; color: var(--us-text-strong);">Total Bayar</span>
                        <span style="font-size: 1.25rem; font-weight: 700; color: var(--us-accent);">
                            {{ $this->formatRupiah($this->harga_total) }}
                        </span>
                    </div>

                    {{-- Badge diskon (dinamis mengikuti durasi terpilih) --}}
                    @if($this->bonus_bulan > 0)
                    <div style="margin-top: 0.875rem; padding: 0.5rem 0.75rem; background: var(--us-success-bg); border: 1px solid var(--us-success-border); border-radius: 0.5rem; font-size: 0.75rem; color: var(--us-success-text); display: flex; align-items: center; gap: 0.375rem;">
                        <span>🎉</span>
                        <span>Hemat {{ $this->bonus_bulan }} bulan (bayar {{ $this->bulan_bayar }}, dapat {{ $this->durasi_bulan }})</span>
                    </div>
                    @endif

                </div>
            </div>

            {{-- Info pembayaran --}}
            <div style="margin-top: 0.75rem; padding: 0.875rem 1rem; background: var(--us-info-bg); border: 1px solid var(--us-info-border); border-radius: 0.75rem; font-size: 0.75rem; color: var(--us-info-text); line-height: 1.5;">
                <div style="font-weight: 600; color: var(--us-info-title); margin-bottom: 0.25rem;">📋 Cara pembayaran</div>
                Setelah klik tombol di bawah, Anda akan mendapat nomor invoice dan instruksi transfer bank.
            </div>

            {{-- Tombol bayar: selalu di bawah ringkasan, termasuk saat kolom di-stack di mobile --}}
            <div style="margin-top: 0.75rem;">
                {{ $this->prosesPembayaranAction }}
            </div>

        </div>
        {{-- /KANAN --}}

    </div>

</div>

</x-filament-panels::page>
