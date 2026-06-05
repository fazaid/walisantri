{{-- File: resources/views/wali/dashboard.blade.php --}}
@extends('wali.layouts.app')

@section('title', 'Pilih Santri')
@section('subtitle', 'Selamat datang, ' . $wali->name)

@section('content')

    {{-- ══════════════════════════════════════════════════════════════════════
         2A. Selector Santri Aktif (tampil hanya jika > 1 anak)
    ════════════════════════════════════════════════════════════════════════ --}}
    @if($anakList->count() > 1)
    <div class="px-0 pt-0 pb-2">
        <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-hide">
            @foreach($anakList as $anak)
            <a href="{{ route('wali.dashboard', ['santri_id' => $anak->id]) }}"
               class="flex-shrink-0 px-4 py-2 rounded-full text-sm font-medium transition-colors
                      {{ $anak->id == $activeSantriId
                         ? 'bg-teal-700 text-white'
                         : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                {{ explode(' ', $anak->nama_lengkap)[0] }}
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════
         2B. Summary Overview Cards (2×2 grid)
    ════════════════════════════════════════════════════════════════════════ --}}
    @if($activeSantri)
    <div class="pb-2">

        <p class="text-xs text-gray-500 mb-3">
            Pemantauan •
            <span class="font-medium text-gray-700">{{ $activeSantri->nama_lengkap }}</span>
            • {{ $activeSantri->kelas?->nama_kelas }}
        </p>

        <div class="grid grid-cols-2 gap-3">

            {{-- Card 1: Hafalan --}}
            <div class="bg-green-50 rounded-2xl p-4 border border-green-100">
                <p class="text-xs text-green-600 font-medium mb-1">📖 Hafalan</p>
                <p class="text-2xl font-bold text-green-800 leading-tight">{{ $estimasiJuz }}</p>
                <p class="text-xs text-green-600">juz tercapai</p>
            </div>

            {{-- Card 2: Amalan Minggu Ini --}}
            <div class="bg-blue-50 rounded-2xl p-4 border border-blue-100">
                <p class="text-xs text-blue-600 font-medium mb-1">✅ Amalan</p>
                <p class="text-2xl font-bold text-blue-800 leading-tight">{{ $persentaseAmalan }}%</p>
                <p class="text-xs text-blue-600">minggu ini</p>
                <div class="mt-2 h-1.5 bg-blue-100 rounded-full overflow-hidden">
                    <div class="h-full bg-blue-500 rounded-full"
                         style="width: {{ $persentaseAmalan }}%"></div>
                </div>
            </div>

            {{-- Card 3: Status Kesehatan --}}
            <div class="bg-rose-50 rounded-2xl p-4 border border-rose-100">
                <p class="text-xs text-rose-600 font-medium mb-1">🏥 Kesehatan</p>
                @if($kesehatanTerkini)
                    @php
                        $statusInfo = match($kesehatanTerkini->status_pemulihan) {
                            'Rawat_Mandiri'   => ['text' => 'Rawat Mandiri',  'class' => 'text-yellow-700'],
                            'Istirahat_Total' => ['text' => 'Istirahat Total', 'class' => 'text-red-700'],
                            'Rujukan_Luar'    => ['text' => 'Rujuk Luar',     'class' => 'text-red-800'],
                            default           => ['text' => 'Sehat',          'class' => 'text-green-700'],
                        };
                    @endphp
                    <p class="text-sm font-bold {{ $statusInfo['class'] }} leading-tight">
                        {{ $statusInfo['text'] }}
                    </p>
                    <p class="text-xs text-rose-400 mt-1">
                        {{ \Carbon\Carbon::parse($kesehatanTerkini->tanggal_periksa)->diffForHumans() }}
                    </p>
                @else
                    <p class="text-sm font-bold text-green-700 leading-tight">Sehat</p>
                    <p class="text-xs text-rose-400 mt-1">Tidak ada rekam medis</p>
                @endif
            </div>

            {{-- Card 4: Rapor Tahfidz Terakhir --}}
            <div class="bg-amber-50 rounded-2xl p-4 border border-amber-100">
                <p class="text-xs text-amber-600 font-medium mb-1">📋 Rapor</p>
                @if($raporTerakhir)
                    <p class="text-2xl font-bold text-amber-800 leading-tight">
                        {{ $raporTerakhir->nilai_tilawah ?? '-' }}
                    </p>
                    <p class="text-xs text-amber-600">
                        Tilawah • {{ str_replace('_', ' ', $raporTerakhir->periode) }}
                    </p>
                @else
                    <p class="text-sm font-bold text-gray-400 leading-tight">Belum ada</p>
                    <p class="text-xs text-amber-500 mt-1">Rapor belum tersedia</p>
                @endif
            </div>

        </div>

        {{-- Link ke rapor lengkap --}}
        <a href="{{ route('wali.rapor', ['santri_id' => $activeSantriId]) }}"
           class="mt-3 flex items-center justify-center gap-1 text-xs text-teal-700
                  font-medium py-2 hover:text-teal-900 transition-colors">
            Lihat Rapor Lengkap →
        </a>

    </div>

    {{-- Divider sebelum list santri --}}
    <div class="border-t border-gray-100 my-2"></div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════
         LIST SANTRI — tidak diubah dari versi sebelumnya
    ════════════════════════════════════════════════════════════════════════ --}}
    <div class="space-y-3">
        <p class="text-sm text-gray-500 mb-4">Pilih santri yang ingin Anda pantau:</p>

        @forelse($anakList as $santri)
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
                    <p class="text-sm text-gray-500">{{ $santri->kelas?->nama_kelas }} · {{ $santri->kamar?->nama_kamar }}</p>
                    <p class="text-xs text-teal-600 mt-0.5">{{ $santri->pesantren->nama_pesantren }}</p>
                </div>
                {{-- Arrow --}}
                <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>
        @empty
        <div class="text-center py-8 text-gray-400">
            <p class="text-sm">Belum ada data santri yang terhubung.</p>
        </div>
        @endforelse
    </div>

@endsection
