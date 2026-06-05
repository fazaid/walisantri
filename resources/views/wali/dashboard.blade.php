{{-- File: resources/views/wali/dashboard.blade.php --}}
@extends('wali.layouts.app')

@section('title', 'Beranda')
@section('subtitle', config('app.name'))

@section('content')
<div class="space-y-5">

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
            <span class="font-medium">{{ $alert->santri->nama_lengkap }}</span>
            — {{ str_replace('_', ' ', $alert->status_pemulihan) }}
            sejak {{ $alert->tanggal_periksa->translatedFormat('d M Y') }}
        </p>
        @endforeach
    </div>
    @endif

    {{-- List Santri --}}
    <div>
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Anak Anda</p>
        @forelse($anakList as $santri)
        @php
            $setoran  = $setoranTerakhir[$santri->id] ?? null;
            $amalan   = $persentaseAmalan[$santri->id] ?? 0;
            $sakit    = $alertKesehatan->has($santri->id);
            $progressColor = $amalan >= 70 ? 'bg-teal-500' : ($amalan >= 40 ? 'bg-yellow-400' : 'bg-red-400');
        @endphp
        <a href="{{ route('wali.santri.show', $santri->id) }}"
           class="block bg-white rounded-2xl shadow-sm border {{ $sakit ? 'border-red-200' : 'border-gray-100' }} p-4 mb-3 hover:shadow-md transition-shadow active:scale-[0.98]">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-11 h-11 rounded-full {{ $sakit ? 'bg-red-100' : 'bg-teal-100' }} flex items-center justify-center flex-shrink-0">
                    <span class="{{ $sakit ? 'text-red-700' : 'text-teal-700' }} font-bold text-base">
                        {{ strtoupper(substr($santri->nama_lengkap, 0, 1)) }}
                    </span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-800 truncate">{{ $santri->nama_lengkap }}</p>
                    <p class="text-xs text-gray-500">
                        {{ $santri->kelas?->nama_kelas ?? '—' }} · {{ $santri->kamar?->nama_kamar ?? '—' }}
                    </p>
                </div>
                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>

            {{-- Setoran terakhir & amalan --}}
            <div class="border-t border-gray-50 pt-2 space-y-2">
                {{-- Setoran tahfidz terakhir --}}
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-400 w-20 flex-shrink-0">📖 Setoran</span>
                    @if($setoran)
                    <span class="text-xs text-gray-600">
                        {{ $setoran->nama_surah }}
                        <span class="text-gray-400">· {{ $setoran->tanggal->translatedFormat('d M') }}</span>
                    </span>
                    @else
                    <span class="text-xs text-gray-400">Belum ada</span>
                    @endif
                </div>

                {{-- Amalan minggu ini --}}
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-400 w-20 flex-shrink-0">✨ Amalan</span>
                    <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full {{ $progressColor }} rounded-full transition-all"
                             style="width: {{ $amalan }}%"></div>
                    </div>
                    <span class="text-xs text-gray-500 w-8 text-right">{{ $amalan }}%</span>
                </div>
            </div>
        </a>
        @empty
        <div class="text-center py-6 text-sm text-gray-400 bg-white rounded-2xl border border-gray-100">
            Belum ada santri yang terhubung.
        </div>
        @endforelse
    </div>

    {{-- Pengumuman --}}
    @if($pengumuman->isNotEmpty() || $pengumumanCentral->isNotEmpty())
    <div>
        <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Pengumuman</p>
            <a href="{{ route('wali.pengumuman') }}" class="text-xs text-teal-600 hover:text-teal-800">Lihat semua →</a>
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
@endsection
