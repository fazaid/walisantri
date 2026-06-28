{{-- resources/views/wali/pengumuman.blade.php --}}
@extends('wali.layouts.app')

@section('title', 'Pengumuman')
@section('subtitle', 'Info terbaru untuk wali santri')

@section('content')
<div class="space-y-4">

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
