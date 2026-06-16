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

    $statusConfig = match($status) {
        'active'    => ['label' => 'Aktif',        'color' => '#059669', 'bg' => '#ecfdf5', 'border' => '#a7f3d0', 'dot' => '#10b981'],
        'trial'     => ['label' => 'Trial',        'color' => '#2563eb', 'bg' => '#eff6ff', 'border' => '#bfdbfe', 'dot' => '#3b82f6'],
        'expired'   => ['label' => 'Kadaluwarsa',  'color' => '#dc2626', 'bg' => '#fff1f2', 'border' => '#fecdd3', 'dot' => '#ef4444'],
        'suspended' => ['label' => 'Ditangguhkan', 'color' => '#d97706', 'bg' => '#fffbeb', 'border' => '#fde68a', 'dot' => '#f59e0b'],
        default     => ['label' => '—',            'color' => '#6b7280', 'bg' => '#f9fafb', 'border' => '#e5e7eb', 'dot' => '#9ca3af'],
    };

    $kuotaLabel  = $kuota > 0 ? number_format($kuota, 0, ',', '.') . ' santri' : '—';
    $paketConfig = match($pesantren?->paket_langganan) {
        'rintisan'   => ['label' => 'Rintisan',   'kuota' => $kuotaLabel, 'color' => '#2563eb', 'bg' => '#eff6ff'],
        'tumbuh'     => ['label' => 'Tumbuh',     'kuota' => $kuotaLabel, 'color' => '#059669', 'bg' => '#ecfdf5'],
        'berkembang' => ['label' => 'Berkembang', 'kuota' => $kuotaLabel, 'color' => '#d97706', 'bg' => '#fffbeb'],
        'maju'       => ['label' => 'Maju',       'kuota' => $kuotaLabel, 'color' => '#7c3aed', 'bg' => '#f5f3ff'],
        default      => ['label' => '—',          'kuota' => '—',         'color' => '#6b7280', 'bg' => '#f9fafb'],
    };

    $expiredLabel = $expiredAt
        ? ($sisaHari > 0
            ? $expiredAt->translatedFormat('d F Y') . ' (' . $sisaHari . ' hari lagi)'
            : ($sisaHari === 0 ? 'Berakhir hari ini' : 'Telah berakhir ' . abs($sisaHari) . ' hari lalu'))
        : '—';

    $progressColor = $persen >= 90 ? '#ef4444' : ($persen >= 70 ? '#f59e0b' : '#10b981');
    $progressBg    = $persen >= 90 ? '#fef2f2' : ($persen >= 70 ? '#fffbeb' : '#f0fdf4');
@endphp

<div style="display: flex; flex-direction: column; gap: 1.5rem;">

    {{-- ROW 1: 3 stat cards --}}
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">

        {{-- Status --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.25rem; display: flex; flex-direction: column; gap: 0.75rem;">
            <div style="font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">
                Status Langganan
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span style="width: 0.625rem; height: 0.625rem; border-radius: 9999px; background: {{ $statusConfig['dot'] }}; flex-shrink: 0;"></span>
                <span style="font-size: 1.125rem; font-weight: 700; color: {{ $statusConfig['color'] }};">
                    {{ $statusConfig['label'] }}
                </span>
            </div>
            <div style="font-size: 0.8125rem; color: #6b7280;">{{ $expiredLabel }}</div>
        </div>

        {{-- Paket --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.25rem; display: flex; flex-direction: column; gap: 0.75rem;">
            <div style="font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">
                Paket Aktif
            </div>
            <div style="display: flex; align-items: center; gap: 0.625rem;">
                <span style="font-size: 1.125rem; font-weight: 700; color: #111827;">
                    {{ $paketConfig['label'] }}
                </span>
                <span style="font-size: 0.7rem; font-weight: 600; color: {{ $paketConfig['color'] }}; background: {{ $paketConfig['bg'] }}; padding: 0.15rem 0.5rem; border-radius: 9999px;">
                    Aktif
                </span>
            </div>
            <div style="font-size: 0.8125rem; color: #6b7280;">Maks. {{ $paketConfig['kuota'] }}</div>
        </div>

        {{-- Kuota ringkas --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.25rem; display: flex; flex-direction: column; gap: 0.75rem;">
            <div style="font-size: 0.75rem; font-weight: 500; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">
                Kuota Santri
            </div>
            <div style="font-size: 1.125rem; font-weight: 700; color: #111827;">
                {{ number_format($santriAktif, 0, ',', '.') }}
                <span style="font-size: 0.875rem; font-weight: 400; color: #9ca3af;">/ {{ number_format($kuota, 0, ',', '.') }}</span>
            </div>
            <div style="font-size: 0.8125rem; color: {{ $progressColor }}; font-weight: 500;">
                {{ $persen }}% terpakai
            </div>
        </div>

    </div>

    {{-- ROW 2: Progress kuota --}}
    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
            <div>
                <div style="font-weight: 600; color: #111827; font-size: 0.9375rem; margin-bottom: 0.25rem;">Penggunaan Kuota Santri</div>
                <div style="font-size: 0.8125rem; color: #6b7280;">
                    {{ number_format($santriAktif, 0, ',', '.') }} dari {{ number_format($kuota, 0, ',', '.') }} santri aktif
                </div>
            </div>
            <span style="font-size: 1.5rem; font-weight: 700; color: {{ $progressColor }};">{{ $persen }}%</span>
        </div>

        {{-- Progress track --}}
        <div style="height: 0.625rem; background: #f3f4f6; border-radius: 9999px; overflow: hidden;">
            <div style="height: 100%; width: {{ min($persen, 100) }}%; background: {{ $progressColor }}; border-radius: 9999px; transition: width 0.3s ease;"></div>
        </div>

        @if($persen >= 90)
        <div style="margin-top: 0.875rem; padding: 0.625rem 0.875rem; background: #fff1f2; border: 1px solid #fecdd3; border-radius: 0.5rem; font-size: 0.8125rem; color: #dc2626; display: flex; align-items: center; gap: 0.5rem;">
            <span>⚠️</span>
            <span>Kuota hampir penuh — upgrade paket untuk menambah kapasitas</span>
        </div>
        @endif
    </div>

    {{-- ROW 3: Info + Order (2 kolom) --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">

        {{-- Informasi Pesantren --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.5rem;">
            <div style="font-weight: 600; color: #111827; font-size: 0.9375rem; margin-bottom: 1rem;">Informasi Pesantren</div>
            <div style="display: flex; flex-direction: column; gap: 0;">
                @foreach([
                    ['Nama Pesantren',  $pesantren?->nama_pesantren ?? '—'],
                    ['Slug / Subdomain', $pesantren?->slug ?? '—'],
                    ['Bergabung Sejak',  $pesantren?->created_at?->translatedFormat('d F Y') ?? '—'],
                ] as [$label, $value])
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #f9fafb;">
                    <span style="font-size: 0.8125rem; color: #6b7280;">{{ $label }}</span>
                    <span style="font-size: 0.8125rem; font-weight: 500; color: #111827; text-align: right; max-width: 60%;">{{ $value }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Order aktif / CTA upgrade --}}
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.5rem; display: flex; flex-direction: column;">
            @if($activeOrder)
                <div style="font-weight: 600; color: #111827; font-size: 0.9375rem; margin-bottom: 1rem;">Order Sedang Berjalan</div>
                <div style="display: flex; flex-direction: column; gap: 0; flex: 1;">
                    @foreach([
                        ['Nomor Order', $activeOrder->nomor_order],
                        ['Paket Tujuan', ucfirst($activeOrder->paket_target->value)],
                        ['Durasi', $activeOrder->durasi_bulan . ' bulan' . ($activeOrder->bonus_bulan > 0 ? ' + ' . $activeOrder->bonus_bulan . ' bonus' : '')],
                        ['Total', 'Rp ' . number_format($activeOrder->harga_total, 0, ',', '.')],
                    ] as [$label, $value])
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #f9fafb;">
                        <span style="font-size: 0.8125rem; color: #6b7280;">{{ $label }}</span>
                        <span style="font-size: 0.8125rem; font-weight: 500; color: #111827;">{{ $value }}</span>
                    </div>
                    @endforeach
                </div>
                <div style="margin-top: 1.25rem; display: flex; align-items: center; gap: 0.75rem;">
                    @php
                        $orderStatusCfg = [
                            'pending_payment'       => ['bg' => '#fffbeb', 'color' => '#d97706', 'border' => '#fde68a'],
                            'awaiting_confirmation' => ['bg' => '#eff6ff', 'color' => '#2563eb', 'border' => '#bfdbfe'],
                            'confirmed'             => ['bg' => '#ecfdf5', 'color' => '#059669', 'border' => '#a7f3d0'],
                        ][$activeOrder->status->value] ?? ['bg' => '#f9fafb', 'color' => '#6b7280', 'border' => '#e5e7eb'];
                    @endphp
                    <span style="font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.75rem; border-radius: 9999px; background: {{ $orderStatusCfg['bg'] }}; color: {{ $orderStatusCfg['color'] }}; border: 1px solid {{ $orderStatusCfg['border'] }};">
                        {{ $activeOrder->status->label() }}
                    </span>
                    <a href="{{ \App\Filament\Pages\OrderInvoicePage::getUrl(['order' => $activeOrder->id]) }}"
                       style="font-size: 0.8125rem; color: #0d9488; font-weight: 500; text-decoration: none;">
                        Lihat Invoice →
                    </a>
                </div>
            @else
                {{-- CTA upgrade --}}
                <div style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; gap: 1rem; padding: 1rem 0;">
                    <div style="width: 3rem; height: 3rem; background: #f0fdfa; border-radius: 9999px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                        🚀
                    </div>
                    <div>
                        <div style="font-weight: 600; color: #111827; margin-bottom: 0.25rem;">Ingin lebih banyak kuota?</div>
                        <div style="font-size: 0.8125rem; color: #6b7280; line-height: 1.5;">
                            Upgrade paket untuk menambah kuota santri dan memperpanjang masa aktif.
                        </div>
                    </div>
                    <a href="{{ \App\Filament\Pages\UpgradePage::getUrl() }}"
                       style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1.25rem; background: #0d9488; color: white; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600; text-decoration: none;">
                        Upgrade Paket
                    </a>
                </div>
            @endif
        </div>

    </div>

</div>

</x-filament-panels::page>
