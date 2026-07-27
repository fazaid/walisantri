<x-filament-panels::page>
@php
    $pesantren   = $this->getPesantren();
    $activeOrder = $this->getActiveOrder();
    $status      = $pesantren?->status_berlangganan;
    $expiredAt   = $pesantren?->expired_at
        ? \Carbon\Carbon::parse($pesantren->expired_at) : null;
    $santriAktif = \App\Models\Santri::where('pesantren_id', $pesantren?->id)
        ->where('status_aktif', true)->count();
    $kuota       = $pesantren?->max_santri_kuota ?? 0;
    $persen      = $kuota > 0 ? round(($santriAktif / $kuota) * 100) : 0;
    $sisaHari    = $expiredAt ? (int) now()->diffInDays($expiredAt, false) : null;

    // 'tone' merujuk ke set CSS variable --bp-{tone}-{text,bg,border,dot}
    // yang punya varian light & dark di blok <style> di bawah.
    $statusConfig = match($status) {
        'active'    => ['label' => 'Aktif',        'tone' => 'success'],
        'trial'     => ['label' => 'Trial',        'tone' => 'info'],
        'expired'   => ['label' => 'Kadaluwarsa',  'tone' => 'danger'],
        'suspended' => ['label' => 'Ditangguhkan', 'tone' => 'warning'],
        default     => ['label' => '—',            'tone' => 'neutral'],
    };

    $kuotaLabel  = $kuota > 0 ? number_format($kuota, 0, ',', '.') . ' santri' : '—';
    $paketConfig = match($pesantren?->paket_langganan) {
        'rintisan'   => ['label' => 'Rintisan',   'kuota' => $kuotaLabel, 'tone' => 'info'],
        'tumbuh'     => ['label' => 'Tumbuh',     'kuota' => $kuotaLabel, 'tone' => 'success'],
        'berkembang' => ['label' => 'Berkembang', 'kuota' => $kuotaLabel, 'tone' => 'warning'],
        'maju'       => ['label' => 'Maju',       'kuota' => $kuotaLabel, 'tone' => 'violet'],
        default      => ['label' => '—',          'kuota' => '—',         'tone' => 'neutral'],
    };

    $expiredLabel = $expiredAt
        ? ($sisaHari > 0
            ? $expiredAt->translatedFormat('d F Y') . ' (' . $sisaHari . ' hari lagi)'
            : ($sisaHari === 0 ? 'Berakhir hari ini' : 'Telah berakhir ' . abs($sisaHari) . ' hari lalu'))
        : '—';

    $progressTone = $persen >= 90 ? 'danger' : ($persen >= 70 ? 'warning' : 'success');
@endphp

<style>
    /* Palet halaman — memakai CSS variable milik Filament (--gray-*, --primary-*,
       --info-*, --success-*, --warning-*, --danger-*) supaya ikut tema panel
       sekaligus punya varian dark mode. */
    .billing-page {
        --bp-card-bg:         white;
        --bp-border:          var(--gray-200);
        --bp-border-soft:     var(--gray-100);
        --bp-text:            var(--gray-950);
        --bp-text-muted:      var(--gray-500);
        --bp-text-faint:      var(--gray-400);
        --bp-track:           var(--gray-100);
        --bp-link:            var(--primary-600);
        --bp-cta-bg:          var(--primary-600);
        --bp-cta-text:        white;
        --bp-accent-soft-bg:  var(--primary-50);

        --bp-success-text:    var(--success-600);
        --bp-success-bg:      var(--success-50);
        --bp-success-border:  var(--success-200);
        --bp-success-dot:     var(--success-500);

        --bp-info-text:       var(--info-600);
        --bp-info-bg:         var(--info-50);
        --bp-info-border:     var(--info-200);
        --bp-info-dot:        var(--info-500);

        --bp-warning-text:    var(--warning-600);
        --bp-warning-bg:      var(--warning-50);
        --bp-warning-border:  var(--warning-200);
        --bp-warning-dot:     var(--warning-500);

        --bp-danger-text:     var(--danger-600);
        --bp-danger-bg:       var(--danger-50);
        --bp-danger-border:   var(--danger-200);
        --bp-danger-dot:      var(--danger-500);

        --bp-neutral-text:    var(--gray-500);
        --bp-neutral-bg:      var(--gray-50);
        --bp-neutral-border:  var(--gray-200);
        --bp-neutral-dot:     var(--gray-400);

        /* Filament tidak punya famili ungu — nilainya ditulis manual. */
        --bp-violet-text:     #7c3aed;
        --bp-violet-bg:       #f5f3ff;
        --bp-violet-border:   #ddd6fe;
        --bp-violet-dot:      #8b5cf6;
    }
    .dark .billing-page {
        --bp-card-bg:         var(--gray-900);
        --bp-border:          var(--gray-800);
        --bp-border-soft:     var(--gray-800);
        --bp-text:            var(--gray-100);
        --bp-text-muted:      var(--gray-400);
        --bp-text-faint:      var(--gray-500);
        --bp-track:           var(--gray-800);
        --bp-link:            var(--primary-400);
        --bp-cta-bg:          var(--primary-500);
        --bp-cta-text:        var(--gray-950);
        --bp-accent-soft-bg:  color-mix(in srgb, var(--primary-400) 15%, transparent);

        --bp-success-text:    var(--success-400);
        --bp-success-bg:      color-mix(in srgb, var(--success-400) 12%, transparent);
        --bp-success-border:  color-mix(in srgb, var(--success-400) 30%, transparent);
        --bp-success-dot:     var(--success-400);

        --bp-info-text:       var(--info-400);
        --bp-info-bg:         color-mix(in srgb, var(--info-400) 12%, transparent);
        --bp-info-border:     color-mix(in srgb, var(--info-400) 30%, transparent);
        --bp-info-dot:        var(--info-400);

        --bp-warning-text:    var(--warning-400);
        --bp-warning-bg:      color-mix(in srgb, var(--warning-400) 12%, transparent);
        --bp-warning-border:  color-mix(in srgb, var(--warning-400) 30%, transparent);
        --bp-warning-dot:     var(--warning-400);

        --bp-danger-text:     var(--danger-400);
        --bp-danger-bg:       color-mix(in srgb, var(--danger-400) 12%, transparent);
        --bp-danger-border:   color-mix(in srgb, var(--danger-400) 30%, transparent);
        --bp-danger-dot:      var(--danger-400);

        --bp-neutral-text:    var(--gray-400);
        --bp-neutral-bg:      var(--gray-800);
        --bp-neutral-border:  var(--gray-700);
        --bp-neutral-dot:     var(--gray-500);

        --bp-violet-text:     #c4b5fd;
        --bp-violet-bg:       color-mix(in srgb, #8b5cf6 15%, transparent);
        --bp-violet-border:   color-mix(in srgb, #8b5cf6 35%, transparent);
        --bp-violet-dot:      #a78bfa;
    }

    /* ROW 1: 3 stat card — desktop 3 kolom */
    .billing-row1 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
    }
    /* ROW 3: Info + Order — desktop 2 kolom */
    .billing-row3 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    /* Tablet: ROW 1 turun ke 2 kolom, ROW 3 ke 1 kolom */
    @media (max-width: 1024px) {
        .billing-row1 { grid-template-columns: repeat(2, 1fr); }
        .billing-row3 { grid-template-columns: minmax(0, 1fr); }
    }
    /* HP: semua jadi 1 kolom */
    @media (max-width: 640px) {
        .billing-row1 { grid-template-columns: minmax(0, 1fr); }
    }
</style>

<div class="billing-page" style="display: flex; flex-direction: column; gap: 1.5rem;">

    {{-- ROW 1: 3 stat cards --}}
    <div class="billing-row1">

        {{-- Status --}}
        <div style="background: var(--bp-card-bg); border: 1px solid var(--bp-border); border-radius: 0.75rem; padding: 1.25rem; display: flex; flex-direction: column; gap: 0.75rem;">
            <div style="font-size: 0.75rem; font-weight: 500; color: var(--bp-text-muted); text-transform: uppercase; letter-spacing: 0.05em;">
                Status Langganan
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span style="width: 0.625rem; height: 0.625rem; border-radius: 9999px; background: var(--bp-{{ $statusConfig['tone'] }}-dot); flex-shrink: 0;"></span>
                <span style="font-size: 1.125rem; font-weight: 700; color: var(--bp-{{ $statusConfig['tone'] }}-text);">
                    {{ $statusConfig['label'] }}
                </span>
            </div>
            <div style="font-size: 0.8125rem; color: var(--bp-text-muted);">{{ $expiredLabel }}</div>
        </div>

        {{-- Paket --}}
        <div style="background: var(--bp-card-bg); border: 1px solid var(--bp-border); border-radius: 0.75rem; padding: 1.25rem; display: flex; flex-direction: column; gap: 0.75rem;">
            <div style="font-size: 0.75rem; font-weight: 500; color: var(--bp-text-muted); text-transform: uppercase; letter-spacing: 0.05em;">
                Paket Aktif
            </div>
            <div style="display: flex; align-items: center; gap: 0.625rem;">
                <span style="font-size: 1.125rem; font-weight: 700; color: var(--bp-text);">
                    {{ $paketConfig['label'] }}
                </span>
                <span style="font-size: 0.7rem; font-weight: 600; color: var(--bp-{{ $paketConfig['tone'] }}-text); background: var(--bp-{{ $paketConfig['tone'] }}-bg); padding: 0.15rem 0.5rem; border-radius: 9999px;">
                    Aktif
                </span>
            </div>
            <div style="font-size: 0.8125rem; color: var(--bp-text-muted);">Maks. {{ $paketConfig['kuota'] }}</div>
        </div>

        {{-- Kuota ringkas --}}
        <div style="background: var(--bp-card-bg); border: 1px solid var(--bp-border); border-radius: 0.75rem; padding: 1.25rem; display: flex; flex-direction: column; gap: 0.75rem;">
            <div style="font-size: 0.75rem; font-weight: 500; color: var(--bp-text-muted); text-transform: uppercase; letter-spacing: 0.05em;">
                Kuota Santri
            </div>
            <div style="font-size: 1.125rem; font-weight: 700; color: var(--bp-text);">
                {{ number_format($santriAktif, 0, ',', '.') }}
                <span style="font-size: 0.875rem; font-weight: 400; color: var(--bp-text-faint);">/ {{ number_format($kuota, 0, ',', '.') }}</span>
            </div>
            <div style="font-size: 0.8125rem; color: var(--bp-{{ $progressTone }}-text); font-weight: 500;">
                {{ $persen }}% terpakai
            </div>
        </div>

    </div>

    {{-- ROW 2: Progress kuota --}}
    <div style="background: var(--bp-card-bg); border: 1px solid var(--bp-border); border-radius: 0.75rem; padding: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
            <div>
                <div style="font-weight: 600; color: var(--bp-text); font-size: 0.9375rem; margin-bottom: 0.25rem;">Penggunaan Kuota Santri</div>
                <div style="font-size: 0.8125rem; color: var(--bp-text-muted);">
                    {{ number_format($santriAktif, 0, ',', '.') }} dari {{ number_format($kuota, 0, ',', '.') }} santri aktif
                </div>
            </div>
            <span style="font-size: 1.5rem; font-weight: 700; color: var(--bp-{{ $progressTone }}-text);">{{ $persen }}%</span>
        </div>

        {{-- Progress track --}}
        <div style="height: 0.625rem; background: var(--bp-track); border-radius: 9999px; overflow: hidden;">
            <div style="height: 100%; width: {{ min($persen, 100) }}%; background: var(--bp-{{ $progressTone }}-dot); border-radius: 9999px; transition: width 0.3s ease;"></div>
        </div>

        @if($persen >= 90)
        <div style="margin-top: 0.875rem; padding: 0.625rem 0.875rem; background: var(--bp-danger-bg); border: 1px solid var(--bp-danger-border); border-radius: 0.5rem; font-size: 0.8125rem; color: var(--bp-danger-text); display: flex; align-items: center; gap: 0.5rem;">
            <span>⚠️</span>
            <span>Kuota hampir penuh — upgrade paket untuk menambah kapasitas</span>
        </div>
        @endif
    </div>

    {{-- ROW 3: Info + Order (2 kolom) --}}
    <div class="billing-row3">

        {{-- Informasi Pesantren --}}
        <div style="background: var(--bp-card-bg); border: 1px solid var(--bp-border); border-radius: 0.75rem; padding: 1.5rem;">
            <div style="font-weight: 600; color: var(--bp-text); font-size: 0.9375rem; margin-bottom: 1rem;">Informasi Pesantren</div>
            <div style="display: flex; flex-direction: column; gap: 0;">
                @foreach([
                    ['Nama Pesantren',  $pesantren?->nama_pesantren ?? '—'],
                    ['Slug / Subdomain', $pesantren?->slug ?? '—'],
                    ['Bergabung Sejak',  $pesantren?->created_at?->translatedFormat('d F Y') ?? '—'],
                ] as [$label, $value])
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--bp-border-soft);">
                    <span style="font-size: 0.8125rem; color: var(--bp-text-muted);">{{ $label }}</span>
                    <span style="font-size: 0.8125rem; font-weight: 500; color: var(--bp-text); text-align: right; max-width: 60%;">{{ $value }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Order aktif / CTA upgrade --}}
        <div style="background: var(--bp-card-bg); border: 1px solid var(--bp-border); border-radius: 0.75rem; padding: 1.5rem; display: flex; flex-direction: column;">
            @if($activeOrder)
                <div style="font-weight: 600; color: var(--bp-text); font-size: 0.9375rem; margin-bottom: 1rem;">Order Sedang Berjalan</div>
                <div style="display: flex; flex-direction: column; gap: 0; flex: 1;">
                    @foreach([
                        ['Nomor Order', $activeOrder->nomor_order],
                        ['Paket Tujuan', ucfirst($activeOrder->paket_target->value)],
                        ['Durasi', $activeOrder->durasi_bulan . ' bulan' . ($activeOrder->bonus_bulan > 0 ? ' + ' . $activeOrder->bonus_bulan . ' bonus' : '')],
                        ['Total', 'Rp ' . number_format($activeOrder->harga_total, 0, ',', '.')],
                    ] as [$label, $value])
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--bp-border-soft);">
                        <span style="font-size: 0.8125rem; color: var(--bp-text-muted);">{{ $label }}</span>
                        <span style="font-size: 0.8125rem; font-weight: 500; color: var(--bp-text);">{{ $value }}</span>
                    </div>
                    @endforeach
                </div>
                <div style="margin-top: 1.25rem; display: flex; align-items: center; gap: 0.75rem;">
                    @php
                        $orderTone = [
                            'pending_payment'       => 'warning',
                            'awaiting_confirmation' => 'info',
                            'confirmed'             => 'success',
                        ][$activeOrder->status->value] ?? 'neutral';
                    @endphp
                    <span style="font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.75rem; border-radius: 9999px; background: var(--bp-{{ $orderTone }}-bg); color: var(--bp-{{ $orderTone }}-text); border: 1px solid var(--bp-{{ $orderTone }}-border);">
                        {{ $activeOrder->status->label() }}
                    </span>
                    <a href="{{ \App\Filament\Pages\OrderInvoicePage::getUrl(['order' => $activeOrder->id]) }}"
                       style="font-size: 0.8125rem; color: var(--bp-link); font-weight: 500; text-decoration: none;">
                        Lihat Invoice →
                    </a>
                </div>
            @else
                {{-- CTA upgrade --}}
                <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; gap: 1rem; padding: 1rem 0;">
                    <div style="width: 3rem; height: 3rem; background: var(--bp-accent-soft-bg); border-radius: 9999px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                        🚀
                    </div>
                    <div>
                        <div style="font-weight: 600; color: var(--bp-text); margin-bottom: 0.25rem;">Ingin lebih banyak kuota?</div>
                        <div style="font-size: 0.8125rem; color: var(--bp-text-muted); line-height: 1.5;">
                            Upgrade paket untuk menambah kuota santri dan memperpanjang masa aktif.
                        </div>
                    </div>
                    <a href="{{ \App\Filament\Pages\UpgradePage::getUrl() }}"
                       style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1.25rem; background: var(--bp-cta-bg); color: var(--bp-cta-text); border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600; text-decoration: none;">
                        Upgrade Paket
                    </a>
                </div>
            @endif
        </div>

    </div>

</div>

</x-filament-panels::page>
