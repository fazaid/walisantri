{{-- resources/views/wali/pengumuman/index.blade.php --}}
@extends('wali.layouts.app')

@section('title', 'Pengumuman')
@section('subtitle', 'Info terbaru untuk wali santri')

@section('content')
<div class="space-y-3">

    @forelse($pengumuman as $item)
    {{-- Card expandable per pengumuman --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden"
         x-data="{ open: false }">

        {{-- Header — klik untuk expand/collapse --}}
        <button type="button"
                @click="open = !open"
                class="w-full text-left px-4 py-3 flex items-start gap-3">

            {{-- Badge sumber --}}
            <span class="mt-0.5 flex-shrink-0 text-xs font-semibold px-2 py-0.5 rounded-full
                {{ $item->badge_color === 'teal'
                    ? 'bg-teal-100 text-teal-700'
                    : 'bg-purple-100 text-purple-700' }}">
                {{ $item->badge }}
            </span>

            <div class="flex-1 min-w-0 text-left">
                <p class="font-semibold text-gray-800 text-sm leading-snug">
                    {{ $item->judul }}
                </p>
                <p class="text-xs text-gray-400 mt-0.5">
                    {{ $item->created_at->translatedFormat('d M Y') }}
                </p>
            </div>

            {{-- Chevron icon --}}
            <svg class="w-4 h-4 text-gray-400 flex-shrink-0 mt-1 transition-transform duration-200"
                 :class="open ? 'rotate-180' : ''"
                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        {{-- Expandable isi -- Alpine x-show with fade transition --}}
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-1"
             class="px-4 pb-4 border-t border-gray-50">
            <div class="prose prose-sm max-w-none text-gray-700 mt-3 text-sm leading-relaxed">
                {!! $item->isi !!}
            </div>
        </div>

    </div>
    @empty
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-4 py-10 text-center">
        <p class="text-3xl mb-2">📭</p>
        <p class="text-sm font-medium text-gray-600">Belum ada pengumuman</p>
        <p class="text-xs text-gray-400 mt-1">Pengumuman dari pesantren dan pusat akan tampil di sini.</p>
    </div>
    @endforelse

</div>
@endsection
