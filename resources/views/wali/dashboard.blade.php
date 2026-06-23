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

    @if($children->isEmpty())
    <div class="text-center py-10 text-sm text-gray-400 bg-white rounded-2xl border border-gray-100">
        Belum ada santri yang terhubung.
    </div>
    @else

    {{-- Tab Pilih Anak --}}
    @if($children->count() > 1)
    <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-hide" id="child-tabs">
        @foreach($children as $i => $child)
        <button onclick="switchChild({{ $i }})"
                id="tab-{{ $i }}"
                class="flex-shrink-0 px-4 py-2 rounded-full text-sm font-medium transition-colors
                       {{ $i === 0 ? 'bg-teal-700 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
            {{ explode(' ', $child['santri']->nama_lengkap)[0] }}
        </button>
        @endforeach
    </div>
    @endif

    {{-- Panel per Anak --}}
    @foreach($children as $i => $child)
    @php
        $santri    = $child['santri'];
        $rapor     = $child['raporTerakhir'];
        $kesehatan = $child['statusKesehatan'];
        $amalan    = $child['persentaseAmalan'];
        $juz       = $child['juz'];

        [$kBg, $kBorder, $kText, $kLabel] = match($kesehatan['status_pemulihan'] ?? null) {
            'Rawat_Mandiri'   => ['bg-yellow-50','border-yellow-200','text-yellow-700','Rawat Mandiri'],
            'Istirahat_Total' => ['bg-red-50',   'border-red-200',   'text-red-700',   'Istirahat Total'],
            'Rujukan_Luar'    => ['bg-red-50',   'border-red-200',   'text-red-800',   'Rujukan Luar'],
            default           => ['bg-green-50', 'border-green-200', 'text-green-700', 'Sehat'],
        };
        $progressColor = $amalan >= 70 ? 'bg-green-500' : ($amalan >= 40 ? 'bg-yellow-400' : 'bg-red-400');
        if ($rapor) {
            $periodeLabel = match($rapor['periode']) {
                'Semester_Ganjil' => 'Sem. Ganjil',
                'Semester_Genap'  => 'Sem. Genap',
                default           => $rapor['periode'],
            };
            $tahunSingkat = implode('/', array_map(
                fn ($y) => substr(trim($y), 2),
                explode('/', $rapor['tahun_ajaran'])
            ));
        }
    @endphp
    <div id="panel-{{ $i }}" class="{{ $i !== 0 ? 'hidden' : '' }} space-y-4">

        {{-- Info Santri --}}
        <div class="bg-teal-700 text-white rounded-2xl p-4 shadow">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-teal-500 flex items-center justify-center flex-shrink-0">
                    <span class="text-xl font-bold">{{ strtoupper(substr($santri->nama_lengkap, 0, 1)) }}</span>
                </div>
                <div>
                    <p class="font-bold text-base leading-tight">{{ $santri->nama_lengkap }}</p>
                    <p class="text-teal-200 text-xs">NIS: {{ $santri->nis }}</p>
                    <p class="text-teal-200 text-xs">Pembimbing: {{ $santri->pembimbing->name }}</p>
                </div>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 gap-3">

            <div class="bg-teal-50 border border-teal-200 rounded-2xl p-4">
                <div class="flex items-center gap-1.5 mb-1">
                    <span class="text-base">📖</span>
                    <span class="text-xs font-medium text-teal-600">Hafalan</span>
                </div>
                @if($juz['juz_selesai'] > 0 || $juz['juz_sedang'])
                    <p class="text-2xl font-bold text-teal-700">{{ $juz['juz_selesai'] }}<span class="text-sm ml-1">Juz</span></p>
                    @if($juz['juz_sedang'])
                        <p class="text-xs text-teal-500 mt-0.5">Juz {{ $juz['juz_sedang'] }} ({{ $juz['persen_sedang'] }}%)</p>
                    @endif
                @else
                    <p class="text-sm font-medium text-teal-400">Belum ada</p>
                @endif
            </div>

            <div class="bg-purple-50 border border-purple-200 rounded-2xl p-4">
                <div class="flex items-center gap-1.5 mb-1">
                    <span class="text-base">✨</span>
                    <span class="text-xs font-medium text-purple-600">Amalan</span>
                </div>
                <p class="text-2xl font-bold text-purple-700">{{ $amalan }}<span class="text-sm">%</span></p>
                <div class="mt-1.5 h-1.5 rounded-full bg-gray-200 overflow-hidden">
                    <div class="h-full {{ $progressColor }} rounded-full" style="width: {{ $amalan }}%"></div>
                </div>
            </div>

            <div class="{{ $kBg }} border {{ $kBorder }} rounded-2xl p-4">
                <div class="flex items-center gap-1.5 mb-1">
                    <span class="text-base">🏥</span>
                    <span class="text-xs font-medium {{ $kText }}">Kesehatan</span>
                </div>
                <p class="text-sm font-bold {{ $kText }}">{{ $kLabel }}</p>
                @if($kesehatan)
                    <p class="text-xs {{ $kText }} opacity-70">{{ $kesehatan['tanggal_periksa']->translatedFormat('d M Y') }}</p>
                @endif
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4">
                <div class="flex items-center gap-1.5 mb-1">
                    <span class="text-base">⭐</span>
                    <span class="text-xs font-medium text-amber-600">Rapor</span>
                </div>
                @if($rapor)
                    <p class="text-2xl font-bold text-amber-700">{{ $rapor['nilai_hafalan'] }}</p>
                    <p class="text-xs text-amber-600">{{ $periodeLabel }} {{ $tahunSingkat }}</p>
                @else
                    <p class="text-sm font-medium text-amber-400">Belum ada</p>
                @endif
            </div>

        </div>

        {{-- Setoran Tahfidz --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800 text-sm">📖 Setoran Tahfidz</h2>
                <a href="{{ route('wali.santri.tahfidz', $santri->id) }}" class="text-xs text-teal-600">Statistik →</a>
            </div>
            @forelse($child['tahfidzRecent'] as $progress)
            <div class="px-4 py-3 border-b border-gray-50 last:border-0">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-800 text-sm">{{ $progress->nama_surah }}</p>
                        <p class="text-xs text-gray-500">
                            Ayat {{ $progress->ayat_mulai }}–{{ $progress->ayat_selesai }}
                            · {{ $progress->tanggal->translatedFormat('d M Y') }}
                        </p>
                    </div>
                    <div class="flex flex-col items-end gap-1 flex-shrink-0">
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            {{ $progress->tipe_setoran === 'Sabaq' ? 'bg-green-100 text-green-700' :
                               ($progress->tipe_setoran === 'Sabqi' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700') }}">
                            {{ $progress->tipe_setoran }}
                        </span>
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium
                            {{ $progress->nilai_kelancaran === 'Mumtaz' ? 'bg-emerald-100 text-emerald-700' :
                               ($progress->nilai_kelancaran === 'Jayyid Jiddan' ? 'bg-sky-100 text-sky-700' :
                               ($progress->nilai_kelancaran === 'Jayyid' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700')) }}">
                            {{ $progress->nilai_kelancaran }}
                        </span>
                    </div>
                </div>
            </div>
            @empty
            <div class="px-4 py-5 text-center text-sm text-gray-400">Belum ada setoran tahfidz.</div>
            @endforelse
        </div>

        {{-- Riwayat Kesehatan --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800 text-sm">🏥 Riwayat Kesehatan</h2>
                <a href="{{ route('wali.santri.kesehatan', $santri->id) }}" class="text-xs text-teal-600">Statistik →</a>
            </div>
            @forelse($child['kesehatanRecent'] as $item)
            <div class="px-4 py-3 border-b border-gray-50 last:border-0">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-800 text-sm">{{ str_replace('_', ' ', $item->kategori_keluhan) }}</p>
                        <p class="text-xs text-gray-500">{{ $item->tanggal_periksa->translatedFormat('d M Y') }}</p>
                        <p class="text-xs text-gray-400 mt-0.5 line-clamp-1">{{ $item->tindakan_dan_obat }}</p>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium flex-shrink-0
                        {{ $item->status_pemulihan === 'Rawat_Mandiri' ? 'bg-yellow-100 text-yellow-700' :
                           ($item->status_pemulihan === 'Istirahat_Total' ? 'bg-orange-100 text-orange-700' : 'bg-red-100 text-red-700') }}">
                        {{ str_replace('_', ' ', $item->status_pemulihan) }}
                    </span>
                </div>
            </div>
            @empty
            <div class="px-4 py-5 text-center text-sm text-gray-400">Tidak ada riwayat kesehatan.</div>
            @endforelse
        </div>


    </div>{{-- /panel --}}
    @endforeach

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

    {{-- Pengumuman --}}
    @if($pengumuman->isNotEmpty() || $pengumumanCentral->isNotEmpty())
    <div>
        <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Pengumuman</p>
            <a href="{{ route('wali.pengumuman') }}" class="text-xs text-teal-600">Lihat semua →</a>
        </div>
        <div class="space-y-2">
            @foreach($pengumuman->merge($pengumumanCentral)->sortByDesc('created_at')->take(5) as $item)
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

<script>
function switchChild(index) {
    const count = {{ $children->count() }};
    for (let i = 0; i < count; i++) {
        document.getElementById('panel-' + i).classList.toggle('hidden', i !== index);
        const tab = document.getElementById('tab-' + i);
        if (tab) {
            tab.className = tab.className
                .replace('bg-teal-700 text-white', 'bg-gray-100 text-gray-600 hover:bg-gray-200')
                .replace('bg-gray-100 text-gray-600 hover:bg-gray-200', 'bg-gray-100 text-gray-600 hover:bg-gray-200');
            if (i === index) {
                tab.classList.remove('bg-gray-100', 'text-gray-600', 'hover:bg-gray-200');
                tab.classList.add('bg-teal-700', 'text-white');
            }
        }
    }
}
</script>
@endsection
