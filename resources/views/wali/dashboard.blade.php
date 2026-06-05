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

    {{-- List Santri --}}
    <div>
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Anak Anda</p>
        @forelse($anakList as $santri)
        <a href="{{ route('wali.santri.show', $santri->id) }}"
           class="flex items-center gap-4 bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-3 hover:shadow-md transition-shadow active:scale-[0.98]">
            <div class="w-11 h-11 rounded-full bg-teal-100 flex items-center justify-center flex-shrink-0">
                <span class="text-teal-700 font-bold text-base">
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
                <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ $item->isi_maklumat }}</p>
                <p class="text-xs text-gray-400 mt-2">{{ $item->created_at->diffForHumans() }}</p>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
