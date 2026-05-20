{{-- File: resources/views/wali/santri/show.blade.php --}}
@extends('wali.layouts.app')

@section('title', $santri->nama_lengkap)
@section('subtitle', $santri->kelas . ' · ' . $santri->kamar)
@section('back_url', route('wali.dashboard'))

@section('content')
<div class="space-y-5">

    {{-- Info Card Santri --}}
    <div class="bg-teal-700 text-white rounded-2xl p-4 shadow">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-teal-500 flex items-center justify-center flex-shrink-0">
                <span class="text-2xl font-bold">
                    {{ strtoupper(substr($santri->nama_lengkap, 0, 1)) }}
                </span>
            </div>
            <div>
                <p class="font-bold text-lg leading-tight">{{ $santri->nama_lengkap }}</p>
                <p class="text-teal-200 text-sm">NIS: {{ $santri->nis }}</p>
                <p class="text-teal-200 text-sm">Pembimbing: {{ $santri->pembimbing->name }}</p>
            </div>
        </div>
    </div>

    {{-- Riwayat Setoran Tahfidz --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">📖 Setoran Tahfidz</h2>
            <span class="text-xs text-gray-400">10 terbaru</span>
        </div>

        @forelse($tahfidzRecent as $progress)
        <div class="px-4 py-3 border-b border-gray-50 last:border-0">
            <div class="flex items-start justify-between gap-2">
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-800 text-sm">{{ $progress->nama_surah }}</p>
                    <p class="text-xs text-gray-500">
                        Ayat {{ $progress->ayat_mulai }}–{{ $progress->ayat_selesai }}
                        · {{ $progress->tanggal->translatedFormat('d M Y') }}
                    </p>
                    @if($progress->catatan_evaluasi)
                    <p class="text-xs text-gray-400 mt-1 italic">{{ $progress->catatan_evaluasi }}</p>
                    @endif
                </div>
                <div class="flex flex-col items-end gap-1 flex-shrink-0">
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium
                        {{ $progress->tipe_setoran === 'Sabaq' ? 'bg-green-100 text-green-700' :
                           ($progress->tipe_setoran === 'Sabqi' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700') }}">
                        {{ $progress->tipe_setoran }}
                    </span>
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium
                        {{ $progress->nilai_kelancaran === 'Mumtaz' ? 'bg-emerald-100 text-emerald-700' :
                           ($progress->nilai_kelancaran === 'Jayyid Jiddan' ? 'bg-sky-100 text-sky-700' :
                           ($progress->nilai_kelancaran === 'Jayyid' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700')) }}">
                        {{ $progress->nilai_kelancaran }}
                    </span>
                </div>
            </div>
        </div>
        @empty
        <div class="px-4 py-6 text-center text-sm text-gray-400">
            Belum ada data setoran tahfidz.
        </div>
        @endforelse
    </div>

    {{-- Riwayat Kesehatan --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">🏥 Riwayat Kesehatan</h2>
            <span class="text-xs text-gray-400">5 terbaru</span>
        </div>

        @forelse($kesehatanRecent as $kesehatan)
        <div class="px-4 py-3 border-b border-gray-50 last:border-0">
            <div class="flex items-start justify-between gap-2">
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-800 text-sm">
                        {{ str_replace('_', ' ', $kesehatan->kategori_keluhan) }}
                    </p>
                    <p class="text-xs text-gray-500">
                        {{ $kesehatan->tanggal_periksa->translatedFormat('d M Y') }}
                    </p>
                    <p class="text-xs text-gray-600 mt-1">{{ $kesehatan->tindakan_dan_obat }}</p>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full font-medium flex-shrink-0
                    {{ $kesehatan->status_pemulihan === 'Rawat_Mandiri' ? 'bg-green-100 text-green-700' :
                       ($kesehatan->status_pemulihan === 'Istirahat_Total' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                    {{ str_replace('_', ' ', $kesehatan->status_pemulihan) }}
                </span>
            </div>
        </div>
        @empty
        <div class="px-4 py-6 text-center text-sm text-gray-400">
            Tidak ada riwayat kesehatan tercatat.
        </div>
        @endforelse
    </div>

</div>
@endsection