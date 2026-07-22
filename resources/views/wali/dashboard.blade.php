{{-- File: resources/views/wali/dashboard.blade.php --}}
@extends('wali.layouts.app')

@section('title', 'Beranda')
@section('subtitle', config('app.name'))

@section('content')
<div class="space-y-4">

    {{-- Sapaan --}}
    <div>
        <p class="text-sm text-gray-500">Assalamu'alaikum,</p>
        <p class="text-xl font-bold text-gray-800">{{ $wali->name }}</p>
    </div>

    {{-- Alert Kesehatan --}}
    @if($alertKesehatan->isNotEmpty())
    <div class="bg-red-50 border border-red-200 rounded-2xl p-4 space-y-1">
        <p class="text-sm font-semibold text-red-700">⚠ Perhatian Kesehatan</p>
        @foreach($alertKesehatan as $alert)
        <p class="text-xs text-red-600">
            <span class="font-medium">{{ $alert['nama'] }}</span>
            — {{ str_replace('_', ' ', $alert['status']) }}
            sejak {{ $alert['tanggal_periksa']->translatedFormat('d M Y') }}
        </p>
        @endforeach
    </div>
    @endif

    @if($santri)
        {{-- Wali dengan 1 anak: tampilkan detail penuh langsung, tanpa tab tambahan --}}
        @include('wali.partials.santri-detail', array_merge(['santri' => $santri], $detail))
    @elseif($cards->isNotEmpty())
        {{-- Wali dengan >1 anak: kartu ringkas per anak, tap untuk lihat detail penuh --}}
        <div class="space-y-3">
            @foreach($cards as $card)
            @php
                $cardSantri = $card['santri'];
                $cardJuz    = $card['juz'];
                $cardAmalan = $card['persentaseAmalan'];
                $cardKes    = $card['statusKesehatan'];
                [$kBg, $kText, $kLabel] = match($cardKes['status_pemulihan'] ?? null) {
                    'Rawat_Mandiri'   => ['bg-yellow-50', 'text-yellow-700', 'Rawat Mandiri'],
                    'Istirahat_Total' => ['bg-red-50',    'text-red-700',    'Istirahat Total'],
                    'Rujukan_Luar'    => ['bg-red-50',    'text-red-800',    'Rujukan Luar'],
                    default           => ['bg-green-50',  'text-green-700',  'Sehat'],
                };
            @endphp
            <a href="{{ route('wali.santri.show', $cardSantri->id) }}"
               class="block bg-white rounded-2xl shadow-sm border border-gray-100 p-4 hover:border-teal-200 transition-colors">
                <div class="flex items-center gap-3">
                    @if($cardSantri->foto_profil)
                    <img src="{{ Storage::disk('public')->url($cardSantri->foto_profil) }}"
                         alt="{{ $cardSantri->nama_lengkap }}"
                         class="w-12 h-12 rounded-full object-cover flex-shrink-0">
                    @else
                    <div class="w-12 h-12 rounded-full bg-teal-700 flex items-center justify-center flex-shrink-0">
                        <span class="text-white text-lg font-bold">{{ strtoupper(substr($cardSantri->nama_lengkap, 0, 1)) }}</span>
                    </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-gray-800 text-sm truncate">{{ $cardSantri->nama_lengkap }}</p>
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1.5">
                            <span class="text-xs text-gray-500">📖 {{ number_format($cardJuz['juz_hafal'], 1) }} juz</span>
                            <span class="text-xs text-gray-500">✨ Amalan {{ $cardAmalan }}%</span>
                            <span class="text-xs px-1.5 py-0.5 rounded-full {{ $kBg }} {{ $kText }} font-medium">{{ $kLabel }}</span>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </a>
            @endforeach
        </div>
    @else
        <div class="text-center py-10 text-sm text-gray-400 bg-white rounded-2xl border border-gray-100">
            Belum ada santri yang terhubung.
        </div>
    @endif

    {{-- Notifikasi SPP --}}
    @if($tunggakanSpp > 0)
    <a href="{{ route('wali.spp') }}" class="block bg-orange-50 border border-orange-200 rounded-2xl px-4 py-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <span class="text-xl">💳</span>
                <div>
                    <p class="text-sm font-semibold text-orange-700">
                        {{ $tunggakanSpp }} tagihan SPP belum dibayar
                    </p>
                    <p class="text-xs text-orange-500">Tap untuk lihat detail</p>
                </div>
            </div>
            <svg class="w-4 h-4 text-orange-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </div>
    </a>
    @endif

    {{-- Uang Saku (agregat lintas anak) — login-only, seperti SPP. Sengaja TIDAK
         disurutkan ke magic link/preview karena data finansial & link bisa disebar. --}}
    @if($firstSantriId)
    <a href="{{ route('wali.uang-saku') }}"
       class="block bg-white rounded-2xl shadow-sm border border-gray-100 px-4 py-3 hover:border-teal-200 transition-colors">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="text-xl">💰</span>
                <div>
                    <p class="text-sm font-semibold text-gray-800">Uang Saku</p>
                    <p class="text-xs {{ $totalSaldoUangSaku >= 0 ? 'text-gray-400' : 'text-red-500' }}">
                        Saldo Rp {{ number_format($totalSaldoUangSaku, 0, ',', '.') }}
                    </p>
                </div>
            </div>
            <span class="text-xs font-medium text-teal-600">Detail →</span>
        </div>
    </a>
    @endif

    {{-- Inventaris (agregat lintas anak) — untuk wali >1 anak. Wali 1 anak sudah
         mendapat kartu inventaris per-santri dari partial santri-detail di atas. --}}
    @if(! $santri && $firstSantriId)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-4 py-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="text-xl">📦</span>
                <div>
                    <p class="text-sm font-semibold text-gray-800">Inventaris Santri</p>
                    <p class="text-xs text-gray-400">{{ $totalInventaris }} barang tercatat</p>
                </div>
            </div>
            <a href="{{ route('wali.santri.inventaris', $firstSantriId) }}"
               class="text-xs font-medium text-teal-600 hover:text-teal-800">
                Detail →
            </a>
        </div>
    </div>
    @endif

    {{-- Pengumuman --}}
    @if($pengumuman->isNotEmpty() || $pengumumanGlobal->isNotEmpty())
    <div>
        <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Pengumuman</p>
            <a href="{{ route('wali.pengumuman') }}" class="text-xs text-teal-600">Lihat semua →</a>
        </div>
        <div class="space-y-2">
            @foreach($pengumuman->merge($pengumumanGlobal)->sortByDesc('created_at')->take(5) as $item)
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
                <p class="font-medium text-gray-800 text-sm">{{ $item->judul_maklumat }}</p>
                <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ Str::limit(strip_tags($item->isi_maklumat), 120) }}</p>
                <p class="text-xs text-gray-400 mt-2">{{ $item->created_at->diffForHumans() }}</p>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
