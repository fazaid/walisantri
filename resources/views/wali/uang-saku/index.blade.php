@extends('wali.layouts.app')

@section('title', 'Uang Saku')
@section('subtitle', 'Saldo & riwayat uang saku santri')

@section('content')

    @forelse($santris as $santri)
        @php $saldo = $saldoMap[$santri->id] ?? 0; @endphp
        <a href="{{ route('wali.uang-saku.show', $santri->id) }}"
           class="block mb-4 bg-white border border-gray-100 rounded-2xl px-4 py-4 hover:border-teal-200 transition-colors">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-teal-100 rounded-full flex items-center justify-center text-teal-700 font-bold text-sm flex-shrink-0">
                        {{ strtoupper(substr($santri->nama_lengkap, 0, 1)) }}
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800 text-sm">{{ $santri->nama_lengkap }}</p>
                        @if($santri->kelas)
                            <p class="text-xs text-gray-400">{{ $santri->kelas->nama_kelas }}</p>
                        @endif
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-400 mb-0.5">Saldo</p>
                    <p class="font-bold text-lg {{ $saldo >= 0 ? 'text-teal-700' : 'text-red-600' }}">
                        Rp {{ number_format($saldo, 0, ',', '.') }}
                    </p>
                </div>
            </div>
        </a>
    @empty
        <div class="text-center py-16">
            <p class="text-gray-400 text-sm">Tidak ada data santri</p>
        </div>
    @endforelse

@endsection
