{{-- File: resources/views/admin/billing/index.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing — {{ $pesantren->nama_pesantren }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen">

<div class="max-w-2xl mx-auto px-4 py-8 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Billing & Langganan</h1>
            <p class="text-gray-500 text-sm">{{ $pesantren->nama_pesantren }}</p>
        </div>
        <a href="/admin" class="text-sm text-teal-600 hover:underline">← Kembali ke Panel</a>
    </div>

    {{-- Status Alert --}}
    @if($isSuspended)
    <div class="bg-red-50 border border-red-200 rounded-2xl p-4">
        <p class="text-red-700 font-semibold">⛔ Akun Disuspend</p>
        <p class="text-red-600 text-sm mt-1">Akses panel telah diblokir. Hubungi tim Walisantri untuk reaktivasi.</p>
    </div>
    @elseif($isExpired)
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4">
        <p class="text-amber-700 font-semibold">⚠ Langganan Telah Berakhir</p>
        <p class="text-amber-600 text-sm mt-1">
            Berakhir pada {{ $pesantren->expired_at->translatedFormat('d M Y') }}.
            Segera perbarui untuk memulihkan akses penuh.
        </p>
    </div>
    @else
    <div class="bg-green-50 border border-green-200 rounded-2xl p-4">
        <p class="text-green-700 font-semibold">✅ Langganan Aktif</p>
        <p class="text-green-600 text-sm mt-1">
            Berlaku hingga {{ $pesantren->expired_at?->translatedFormat('d M Y') ?? 'Tidak terbatas' }}.
        </p>
    </div>
    @endif

    {{-- Info Paket --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Detail Paket</h2>
        </div>
        <div class="divide-y divide-gray-50">
            <div class="px-5 py-3 flex justify-between text-sm">
                <span class="text-gray-500">Paket</span>
                <span class="font-medium text-gray-800">{{ $billingInfo['paket'] }}</span>
            </div>
            <div class="px-5 py-3 flex justify-between text-sm">
                <span class="text-gray-500">Biaya Bulanan</span>
                <span class="font-semibold text-teal-700">{{ $billingInfo['formatted'] }}</span>
            </div>
            <div class="px-5 py-3 flex justify-between text-sm">
                <span class="text-gray-500">Kuota Santri</span>
                <span class="font-medium text-gray-800">{{ number_format($billingInfo['kuota_maksimal']) }} santri</span>
            </div>
            @if($billingInfo['faktor_x'])
            <div class="px-5 py-3 flex justify-between text-sm">
                <span class="text-gray-500">Faktor Kelipatan (X)</span>
                <span class="font-medium text-gray-800">{{ $billingInfo['faktor_x'] }}</span>
            </div>
            @endif
        </div>
    </div>

    {{-- Penggunaan Kuota --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Penggunaan Kuota</h2>
        </div>
        <div class="px-5 py-4 space-y-3">
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Santri Aktif</span>
                <span class="font-medium">{{ $santriAktif }} / {{ $billingInfo['kuota_maksimal'] }}</span>
            </div>
            {{-- Progress bar --}}
            @php
                $persen = $billingInfo['kuota_maksimal'] > 0
                    ? min(100, round(($santriAktif / $billingInfo['kuota_maksimal']) * 100))
                    : 0;
                $barColor = $persen >= 90 ? 'bg-red-500' : ($persen >= 70 ? 'bg-amber-500' : 'bg-teal-500');
            @endphp
            <div class="w-full bg-gray-100 rounded-full h-2.5">
                <div class="{{ $barColor }} h-2.5 rounded-full transition-all" style="width: {{ $persen }}%"></div>
            </div>
            <p class="text-xs text-gray-400">Sisa kuota: {{ $sisaKuota }} santri</p>
        </div>
    </div>

    {{-- CTA Hubungi --}}
    <div class="bg-teal-700 rounded-2xl p-5 text-white text-center">
        <p class="font-semibold mb-1">Butuh upgrade atau perpanjangan?</p>
        <p class="text-teal-200 text-sm mb-3">Hubungi tim kami untuk proses pembayaran.</p>
        <a href="https://wa.me/6281200000000?text=Halo,+saya+ingin+perpanjang+langganan+{{ urlencode($pesantren->nama_pesantren) }}"
           target="_blank"
           class="inline-block bg-white text-teal-700 font-semibold text-sm px-5 py-2 rounded-xl hover:bg-teal-50 transition-colors">
            Hubungi via WhatsApp
        </a>
    </div>

</div>

</body>
</html>