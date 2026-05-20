{{-- File: resources/views/wali/dashboard.blade.php --}}
@extends('wali.layouts.app')

@section('title', 'Pilih Santri')
@section('subtitle', 'Selamat datang, ' . $wali->name)

@section('content')
<div class="space-y-3">
    <p class="text-sm text-gray-500 mb-4">Pilih santri yang ingin Anda pantau:</p>

    @foreach($anakList as $santri)
    <a href="{{ route('wali.santri.show', $santri->id) }}"
       class="block bg-white rounded-2xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition-shadow active:scale-[0.98]">
        <div class="flex items-center gap-4">
            {{-- Avatar --}}
            <div class="w-12 h-12 rounded-full bg-teal-100 flex items-center justify-center flex-shrink-0">
                <span class="text-teal-700 font-bold text-lg">
                    {{ strtoupper(substr($santri->nama_lengkap, 0, 1)) }}
                </span>
            </div>
            {{-- Info --}}
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-gray-800 truncate">{{ $santri->nama_lengkap }}</p>
                <p class="text-sm text-gray-500">{{ $santri->kelas }} · {{ $santri->kamar }}</p>
                <p class="text-xs text-teal-600 mt-0.5">{{ $santri->pesantren->nama_pesantren }}</p>
            </div>
            {{-- Arrow --}}
            <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </div>
    </a>
    @endforeach
</div>
@endsection