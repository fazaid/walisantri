{{-- resources/views/wali/pengumuman.blade.php --}}
@extends('wali.layouts.app')

@section('title', 'Pengumuman')
@section('subtitle', 'Info terbaru untuk wali santri')

@section('content')
<div class="space-y-4">

    {{-- ══════════════════════════════════════════════════
         SECTION: Pengumuman dari Pusat
    ══════════════════════════════════════════════════ --}}
    <div class="flex items-center gap-2 mt-1">
        <span class="text-base">📢</span>
        <span class="text-sm font-semibold text-indigo-700">Dari Pusat</span>
    </div>

    @forelse($pengumumanCentral as $item)
    <div class="bg-white rounded-2xl shadow-sm border border-indigo-100 overflow-hidden">
        <div class="px-4 pt-3 pb-2 flex items-start justify-between gap-2">
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-gray-800 text-sm leading-snug">
                    {{ $item->judul_maklumat }}
                </p>
                <p class="text-xs text-gray-400 mt-0.5">
                    {{ $item->created_at->translatedFormat('d M Y') }}
                </p>
            </div>
            <span class="flex-shrink-0 text-xs font-semibold px-2 py-0.5 rounded-full
                         bg-indigo-100 text-indigo-700">
                PUSAT
            </span>
        </div>
        <div class="px-4 pb-3">
            <p class="text-sm text-gray-600 leading-relaxed line-clamp-3">
                {{ strip_tags($item->isi_maklumat) }}
            </p>
        </div>
    </div>
    @empty
    <p class="text-xs text-gray-400 text-center py-3">
        Tidak ada pengumuman dari pusat saat ini.
    </p>
    @endforelse

    {{-- ── Divider --}}
    <div class="relative my-2">
        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200"></div></div>
        <div class="relative flex justify-center">
            <span class="bg-gray-50 px-3 text-xs text-gray-400 font-medium">Pengumuman Pesantren</span>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         SECTION: Pengumuman dari Pesantren
    ══════════════════════════════════════════════════ --}}
    @forelse($pengumumanPesantren as $item)
    <div class="bg-white rounded-2xl shadow-sm border border-emerald-100 overflow-hidden">
        <div class="px-4 pt-3 pb-2 flex items-start justify-between gap-2">
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-gray-800 text-sm leading-snug">
                    {{ $item->judul_maklumat }}
                </p>
                <p class="text-xs text-gray-400 mt-0.5">
                    {{ $item->created_at->translatedFormat('d M Y') }}
                </p>
            </div>
            <span class="flex-shrink-0 text-xs font-semibold px-2 py-0.5 rounded-full
                         bg-emerald-100 text-emerald-700">
                PESANTREN
            </span>
        </div>
        <div class="px-4 pb-3">
            <p class="text-sm text-gray-600 leading-relaxed line-clamp-3">
                {{ strip_tags($item->isi_maklumat) }}
            </p>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-4 py-8 text-center">
        <p class="text-2xl mb-1">📭</p>
        <p class="text-sm font-medium text-gray-500">Belum ada pengumuman dari pesantren</p>
    </div>
    @endforelse

</div>
@endsection
