@extends('wali.layouts.app')

@section('title', 'Inventaris Santri')
@section('subtitle', $santri->nama_lengkap)
@section('back_url', route('wali.santri.show', $santri->id))

@section('content')
<div class="space-y-4">

    {{-- Summary --}}
    <div class="bg-teal-50 border border-teal-200 rounded-2xl p-4">
        <div class="flex items-center gap-3">
            <span class="text-3xl">📦</span>
            <div>
                <p class="text-2xl font-bold text-teal-700 leading-tight">{{ $inventaris->count() }}</p>
                <p class="text-xs text-teal-600">barang tercatat</p>
            </div>
        </div>
    </div>

    {{-- Daftar Inventaris --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @forelse($inventaris as $item)
        <div class="px-4 py-3 border-b border-gray-50 last:border-0">
            <div class="flex items-start justify-between gap-2">
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-800 text-sm">{{ $item->nama_barang_umum }}</p>
                    @if($item->kode_unik_fisik)
                    <p class="text-xs text-gray-400 mt-0.5">Kode: {{ $item->kode_unik_fisik }}</p>
                    @endif
                    @if($item->tanggal_sidak_terakhir)
                    <p class="text-xs text-gray-400 mt-0.5">
                        Sidak: {{ $item->tanggal_sidak_terakhir->translatedFormat('d M Y') }}
                    </p>
                    @endif
                </div>
                <div class="flex flex-col items-end gap-1.5 flex-shrink-0">
                    @if($item->kondisi_barang)
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium
                        {{ match($item->kondisi_barang) {
                            'Baik'      => 'bg-green-100 text-green-700',
                            'Rusak_Ringan' => 'bg-yellow-100 text-yellow-700',
                            'Rusak_Berat'  => 'bg-red-100 text-red-700',
                            default     => 'bg-gray-100 text-gray-600',
                        } }}">
                        {{ str_replace('_', ' ', $item->kondisi_barang) }}
                    </span>
                    @endif
                    @if($item->kuota_regulasi_maksimal)
                    <span class="text-xs text-gray-400">Maks. {{ $item->kuota_regulasi_maksimal }}</span>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="px-4 py-8 text-center text-sm text-gray-400">
            Belum ada data inventaris.
        </div>
        @endforelse
    </div>

</div>
@endsection
