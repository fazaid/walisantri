@extends('wali.layouts.app')

@section('title', 'Tagihan SPP')
@section('subtitle', 'Status pembayaran bulanan')

@section('content')

    {{-- Ringkasan tunggakan --}}
    @if($totalTunggakan > 0)
        <div class="bg-red-50 border border-red-200 rounded-2xl px-4 py-3 mb-5 flex items-center gap-3">
            <span class="text-2xl">⚠️</span>
            <div>
                <p class="font-semibold text-red-700 text-sm">{{ $totalTunggakan }} tagihan belum dibayar</p>
                <p class="text-xs text-red-500">Silakan hubungi admin pesantren untuk pembayaran</p>
            </div>
        </div>
    @else
        <div class="bg-green-50 border border-green-200 rounded-2xl px-4 py-3 mb-5 flex items-center gap-3">
            <span class="text-2xl">✅</span>
            <div>
                <p class="font-semibold text-green-700 text-sm">Semua tagihan lunas</p>
                <p class="text-xs text-green-500">Terima kasih telah membayar tepat waktu</p>
            </div>
        </div>
    @endif

    @forelse($santris as $santri)
        <div class="mb-6">
            {{-- Header santri --}}
            <div class="flex items-center gap-2 mb-3">
                <div class="w-8 h-8 bg-teal-100 rounded-full flex items-center justify-center text-teal-700 font-bold text-sm">
                    {{ strtoupper(substr($santri->nama_lengkap, 0, 1)) }}
                </div>
                <div>
                    <p class="font-semibold text-gray-800 text-sm">{{ $santri->nama_lengkap }}</p>
                    @if($santri->kelas)
                        <p class="text-xs text-gray-400">{{ $santri->kelas->nama_kelas }}</p>
                    @endif
                </div>
            </div>

            @if($santri->tagihanSpp->isEmpty())
                <div class="bg-gray-50 rounded-xl px-4 py-6 text-center">
                    <p class="text-sm text-gray-400">Belum ada tagihan</p>
                </div>
            @else
                <div class="space-y-2">
                    @foreach($santri->tagihanSpp as $tagihan)
                        <div class="bg-white border {{ $tagihan->isLunas() ? 'border-gray-100' : 'border-red-100' }} rounded-xl px-4 py-3 flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-800 text-sm">{{ $tagihan->label_periode }}</p>
                                @if($tagihan->keterangan)
                                    <p class="text-xs text-gray-400">{{ $tagihan->keterangan }}</p>
                                @endif
                                @if($tagihan->isLunas() && $tagihan->pembayaran)
                                    <p class="text-xs text-green-600 mt-0.5">
                                        Dibayar {{ $tagihan->pembayaran->tanggal_bayar->format('d M Y') }}
                                        · {{ \App\Models\PembayaranSpp::$metodeBayar[$tagihan->pembayaran->metode_bayar] ?? $tagihan->pembayaran->metode_bayar }}
                                    </p>
                                @elseif($tagihan->jatuh_tempo)
                                    <p class="text-xs {{ $tagihan->jatuh_tempo->isPast() ? 'text-red-500' : 'text-gray-400' }} mt-0.5">
                                        Jatuh tempo {{ $tagihan->jatuh_tempo->format('d M Y') }}
                                    </p>
                                @endif
                            </div>
                            <div class="text-right flex-shrink-0 ml-3">
                                <p class="font-semibold text-gray-800 text-sm">{{ $tagihan->nominal_rp }}</p>
                                <span class="inline-block text-xs font-medium px-2 py-0.5 rounded-full mt-1
                                    {{ $tagihan->isLunas() ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $tagihan->status->label() }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @empty
        <div class="text-center py-16">
            <p class="text-gray-400 text-sm">Tidak ada data santri</p>
        </div>
    @endforelse

@endsection
