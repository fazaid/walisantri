@extends('wali.layouts.app')

@section('title', $santri->nama_lengkap)
@section('subtitle', 'Riwayat uang saku')
@section('back_url', route('wali.uang-saku'))

@section('content')

    {{-- Kartu saldo --}}
    <div class="bg-teal-700 text-white rounded-2xl px-5 py-5 mb-6">
        <p class="text-teal-200 text-xs mb-1">Saldo Saat Ini</p>
        <p class="text-3xl font-bold">Rp {{ number_format($saldo, 0, ',', '.') }}</p>
        @if($santri->kelas)
            <p class="text-teal-200 text-xs mt-2">{{ $santri->kelas->nama_kelas }}</p>
        @endif
    </div>

    {{-- Riwayat transaksi --}}
    @if($transaksi->isEmpty())
        <div class="bg-white border border-gray-100 rounded-2xl px-4 py-10 text-center">
            <p class="text-gray-400 text-sm">Belum ada transaksi uang saku</p>
        </div>
    @else
        <div class="space-y-2">
            @foreach($transaksi as $item)
                <div class="bg-white border border-gray-100 rounded-xl px-4 py-3 flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-0.5">
                            <span class="inline-block text-xs font-medium px-2 py-0.5 rounded-full
                                {{ $item->jenis->value === 'setoran' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ $item->jenis->label() }}
                            </span>
                            <span class="text-xs text-gray-400">{{ $item->tanggal->format('d M Y') }}</span>
                        </div>
                        @if($item->keterangan)
                            <p class="text-xs text-gray-500 mt-0.5">{{ $item->keterangan }}</p>
                        @endif
                    </div>
                    <p class="font-semibold text-sm ml-3 flex-shrink-0
                        {{ $item->jenis->value === 'setoran' ? 'text-green-700' : 'text-amber-700' }}">
                        {{ $item->jenis->value === 'setoran' ? '+' : '-' }}Rp {{ number_format($item->nominal, 0, ',', '.') }}
                    </p>
                </div>
            @endforeach
        </div>
    @endif

@endsection
