<x-filament-panels::page>
@php
    $pesantren   = $this->getPesantren();
    $status      = $pesantren?->status_berlangganan;
    $expiredAt   = $pesantren?->expired_at
        ? \Carbon\Carbon::parse($pesantren->expired_at) : null;
    $santriAktif = \App\Models\Santri::where('pesantren_id', $pesantren?->id)
        ->where('status_aktif', true)->count();
    $kuota       = $pesantren?->max_santri_kuota ?? 0;
    $persen      = $kuota > 0 ? round(($santriAktif / $kuota) * 100) : 0;
    $sisaHari    = $expiredAt ? (int) now()->diffInDays($expiredAt, false) : null;

    $statusLabel = match($status) {
        'active'    => '✅  Aktif',
        'trial'     => '🔵  Trial',
        'expired'   => '🔴  Kadaluwarsa',
        'suspended' => '🟠  Ditangguhkan',
        default     => '—',
    };
    $paketLabel = match($pesantren?->paket_langganan) {
        'rintisan'   => 'Rintisan — Maks 100 santri',
        'berkembang' => 'Berkembang — Maks 250 santri',
        'akselerasi' => 'Akselerasi — Maks 500 santri',
        'besar'      => 'Besar — Kuota custom',
        default      => '—',
    };
    $expiredLabel = $expiredAt
        ? ($sisaHari > 0
            ? 'Berakhir dalam ' . $sisaHari . ' hari — ' . $expiredAt->translatedFormat('d F Y')
            : ($sisaHari === 0 ? 'Berakhir hari ini' : 'Telah berakhir ' . abs($sisaHari) . ' hari lalu'))
        : '—';
    $kuotaColor = $persen >= 90 ? 'danger' : ($persen >= 70 ? 'warning' : 'success');
@endphp

{{-- ROW 1: Status + Paket --}}
<div class="grid grid-cols-1 gap-4 md:grid-cols-2">

    <x-filament::section>
        <x-slot name="heading">Status Langganan</x-slot>
        <x-slot name="description">{{ $expiredLabel }}</x-slot>
        <p class="text-2xl font-bold tracking-tight">
            {{ $statusLabel }}
        </p>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Paket</x-slot>
        <x-slot name="description">Semua modul tersedia</x-slot>
        <p class="text-2xl font-bold tracking-tight text-gray-700">
            {{ ucfirst($pesantren?->paket_langganan ?? '—') }}
        </p>
        <p class="text-sm text-gray-400 mt-1">{{ $paketLabel }}</p>
    </x-filament::section>

</div>

{{-- ROW 2: Kuota --}}
<x-filament::section>
    <x-slot name="heading">Penggunaan Kuota Santri</x-slot>
    <x-slot name="description">
        {{ $santriAktif }} dari {{ $kuota }} santri aktif
        ({{ $persen }}% terpakai)
    </x-slot>

    <x-filament::badge :color="$kuotaColor" size="lg">
        {{ $santriAktif }} / {{ $kuota }} Santri
    </x-filament::badge>

    <div class="mt-4 h-3 w-full rounded-full bg-gray-100 overflow-hidden">
        <div
            class="h-full rounded-full
                {{ $persen >= 90 ? 'bg-red-400' :
                   ($persen >= 70 ? 'bg-amber-400' : 'bg-emerald-400') }}"
            style="width: {{ $persen }}%">
        </div>
    </div>

    @if($persen >= 90)
    <x-filament::badge color="danger" class="mt-3">
        ⚠ Kuota hampir penuh — pertimbangkan upgrade paket
    </x-filament::badge>
    @endif
</x-filament::section>

{{-- ROW 3: Info Pesantren --}}
<x-filament::section>
    <x-slot name="heading">Informasi Pesantren</x-slot>

    <dl class="divide-y divide-gray-100">
        @foreach([
            'Nama Pesantren'  => $pesantren?->nama_pesantren ?? '—',
            'Slug'            => $pesantren?->slug ?? '—',
            'Bergabung Sejak' => $pesantren?->created_at?->translatedFormat('d F Y') ?? '—',
        ] as $label => $value)
        <div class="flex justify-between items-center py-3">
            <dt class="text-sm text-gray-500">{{ $label }}</dt>
            <dd class="text-sm font-medium text-gray-800">{{ $value }}</dd>
        </div>
        @endforeach
    </dl>
</x-filament::section>

{{-- ROW 4: Perpanjangan --}}
<x-filament::section>
    <x-slot name="heading">Perpanjangan & Upgrade</x-slot>
    <x-slot name="description">
        Hubungi admin Walisantri.com untuk perpanjangan
        masa aktif atau upgrade paket.
    </x-slot>

    <x-filament::badge color="gray">
        📱 Hubungi via WhatsApp
    </x-filament::badge>
</x-filament::section>

</x-filament-panels::page>
