{{-- resources/views/wali/rapor.blade.php --}}
@extends('wali.layouts.app')

@section('title', 'Rapor Santri')
@section('subtitle', 'Rekap perkembangan per periode')
@section('back_url', route('wali.dashboard'))

@section('content')
@php
    $activeTab = request('tab', 'tahfidz');

    $badgeClass = fn($nilai) => match($nilai) {
        'A' => 'bg-green-100 text-green-700',
        'B' => 'bg-blue-100  text-blue-700',
        'C' => 'bg-yellow-100 text-yellow-700',
        default => 'bg-red-100 text-red-700',
    };

    $periodeLabel = fn($p) => match($p) {
        'Semester_Ganjil' => 'Semester Ganjil',
        'Semester_Genap'  => 'Semester Genap',
        default           => $p,
    };
@endphp

<div class="space-y-4">

    {{-- ── Filter: Santri + Tahun Ajaran ──────────────────────────────── --}}
    <form method="GET" action="{{ route('wali.rapor') }}" class="space-y-2">
        <input type="hidden" name="tab" value="{{ $activeTab }}">

        @if($anakList->count() > 1)
        <div>
            <label class="block text-xs text-gray-500 mb-1 font-medium">Pilih Santri</label>
            <select name="santri_id" onchange="this.form.submit()"
                    class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-teal-400">
                @foreach($anakList as $anak)
                <option value="{{ $anak->id }}" {{ $anak->id == $santriId ? 'selected' : '' }}>
                    {{ $anak->nama_lengkap }}
                </option>
                @endforeach
            </select>
        </div>
        @else
            <input type="hidden" name="santri_id" value="{{ $santriId }}">
        @endif

        <div>
            <label class="block text-xs text-gray-500 mb-1 font-medium">Tahun Ajaran</label>
            <select name="tahun_ajaran" onchange="this.form.submit()"
                    class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-teal-400">
                @forelse($tahunList as $tahun)
                <option value="{{ $tahun }}" {{ $tahun === $tahunAjaran ? 'selected' : '' }}>
                    {{ $tahun }}
                </option>
                @empty
                <option value="{{ $tahunAjaran }}">{{ $tahunAjaran }}</option>
                @endforelse
            </select>
        </div>
    </form>

    {{-- ── Tabs ─────────────────────────────────────────────────────────── --}}
    <div class="flex border-b border-gray-200 bg-white rounded-t-xl overflow-hidden shadow-sm">
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'tahfidz']) }}"
           class="flex-1 text-center py-3 text-sm font-medium border-b-2 transition-colors
                  {{ $activeTab === 'tahfidz' ? 'border-teal-600 text-teal-700 bg-teal-50' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            📖 Tahfidz
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'karakter']) }}"
           class="flex-1 text-center py-3 text-sm font-medium border-b-2 transition-colors
                  {{ $activeTab === 'karakter' ? 'border-teal-600 text-teal-700 bg-teal-50' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            🌱 Karakter
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'akademik']) }}"
           class="flex-1 text-center py-3 text-sm font-medium border-b-2 transition-colors
                  {{ $activeTab === 'akademik' ? 'border-teal-600 text-teal-700 bg-teal-50' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            📚 Akademik
        </a>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: TAHFIDZ                                                      --}}
    {{-- ══════════════════════════════════════════════════════════════════ --}}
    @if($activeTab === 'tahfidz')

    @forelse($raporTahfidz as $rapor)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {{-- Badge Periode --}}
        <div class="px-4 py-3 bg-teal-50 border-b border-teal-100 flex items-center justify-between">
            <span class="text-xs font-semibold px-3 py-1 rounded-full bg-teal-700 text-white">
                {{ $periodeLabel($rapor->periode) }}
            </span>
            <span class="text-xs text-teal-600 font-medium">{{ $rapor->tahun_ajaran }}</span>
        </div>

        <div class="p-4 space-y-3">
            {{-- Grid Nilai 2 kolom --}}
            <div class="grid grid-cols-2 gap-3">
                <div class="text-center">
                    <p class="text-xs text-gray-400 mb-1">Nilai Hafalan</p>
                    <span class="inline-block px-3 py-1 rounded-full text-sm font-bold bg-gray-100 text-gray-800">
                        {{ $rapor->nilai_hafalan }}
                    </span>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-400 mb-1">Tilawah</p>
                    <span class="inline-block px-3 py-1 rounded-full text-sm font-bold {{ $badgeClass($rapor->nilai_tilawah) }}">
                        {{ $rapor->nilai_tilawah }}
                    </span>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-400 mb-1">Makhraj</p>
                    <span class="inline-block px-3 py-1 rounded-full text-sm font-bold {{ $badgeClass($rapor->nilai_makhraj) }}">
                        {{ $rapor->nilai_makhraj }}
                    </span>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-400 mb-1">Tajwid</p>
                    <span class="inline-block px-3 py-1 rounded-full text-sm font-bold {{ $badgeClass($rapor->nilai_tajwid) }}">
                        {{ $rapor->nilai_tajwid }}
                    </span>
                </div>
            </div>

            {{-- Rekomendasi Pembimbing --}}
            @if($rapor->rekomendasi_pembimbing)
            <div class="bg-gray-50 rounded-xl p-3 border border-gray-100">
                <p class="text-xs text-gray-400 mb-1 font-medium">Rekomendasi Pembimbing</p>
                <p class="text-sm text-gray-600 italic leading-relaxed">
                    {{ $rapor->rekomendasi_pembimbing }}
                </p>
            </div>
            @endif
        </div>
    </div>
    @empty
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-4 py-10 text-center">
        <p class="text-3xl mb-2">📋</p>
        <p class="text-sm font-medium text-gray-600">Belum ada data rapor</p>
        <p class="text-xs text-gray-400 mt-1">Rapor untuk periode {{ $tahunAjaran }} belum tersedia.</p>
    </div>
    @endforelse

    @endif {{-- /tahfidz tab --}}

    {{-- ══════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: KARAKTER                                                     --}}
    {{-- ══════════════════════════════════════════════════════════════════ --}}
    @if($activeTab === 'karakter')

    @if($raporKarakter)

    {{-- Section Adab --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-4 py-3 bg-emerald-50 border-b border-emerald-100">
            <h3 class="text-sm font-semibold text-emerald-800">🤲 Adab</h3>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach([
                'adab_ustadz'  => 'Adab kepada Ustadz',
                'adab_tamu'    => 'Adab kepada Tamu',
                'adab_asrama'  => 'Adab di Asrama',
                'adab_kelas'   => 'Adab di Kelas',
                'adab_sholat'  => 'Adab Sholat',
                'adab_quran'   => 'Adab Al-Quran',
                'adab_minum'   => 'Adab Minum',
            ] as $field => $label)
            <div class="px-4 py-2.5 flex items-center justify-between">
                <span class="text-sm text-gray-700">{{ $label }}</span>
                <span class="text-xs font-bold px-2.5 py-0.5 rounded-full {{ $badgeClass($raporKarakter->$field) }}">
                    {{ $raporKarakter->$field }}
                </span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Section Kepribadian --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-4 py-3 bg-blue-50 border-b border-blue-100">
            <h3 class="text-sm font-semibold text-blue-800">🌟 Kepribadian</h3>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach([
                'kepribadian_tanggungjawab' => 'Tanggung Jawab',
                'kepribadian_kemandirian'   => 'Kemandirian',
                'kepribadian_kepatuhan'     => 'Kepatuhan',
                'kepribadian_kebersihan'    => 'Kebersihan',
                'kepribadian_mengelola'     => 'Mengelola Diri',
                'kepribadian_kepedulian'    => 'Kepedulian',
                'kepribadian_empati'        => 'Empati',
                'kepribadian_kebersamaan'   => 'Kebersamaan',
                'kepribadian_kedisiplinan'  => 'Kedisiplinan',
            ] as $field => $label)
            <div class="px-4 py-2.5 flex items-center justify-between">
                <span class="text-sm text-gray-700">{{ $label }}</span>
                <span class="text-xs font-bold px-2.5 py-0.5 rounded-full {{ $badgeClass($raporKarakter->$field) }}">
                    {{ $raporKarakter->$field }}
                </span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Catatan Khusus --}}
    @if($raporKarakter->log_kasus_khusus)
    <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-4">
        <p class="text-xs font-semibold text-yellow-800 mb-2">⚠ Catatan Khusus</p>
        <p class="text-sm text-yellow-700 leading-relaxed">{{ $raporKarakter->log_kasus_khusus }}</p>
    </div>
    @endif

    @else
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-4 py-10 text-center">
        <p class="text-3xl mb-2">🌱</p>
        <p class="text-sm font-medium text-gray-600">Belum ada data rapor karakter</p>
        <p class="text-xs text-gray-400 mt-1">Rapor karakter untuk tahun {{ $tahunAjaran }} belum tersedia.</p>
    </div>
    @endif

    @endif {{-- /karakter tab --}}

    {{-- ══════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: AKADEMIK                                                     --}}
    {{-- ══════════════════════════════════════════════════════════════════ --}}
    @if($activeTab === 'akademik')

    @forelse($raporAkademik as $periode => $nilaiList)
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {{-- Badge Periode + Rata-rata --}}
        <div class="px-4 py-3 bg-teal-50 border-b border-teal-100 flex items-center justify-between">
            <span class="text-xs font-semibold px-3 py-1 rounded-full bg-teal-700 text-white">
                {{ $periodeLabel($periode) }}
            </span>
            <span class="text-xs text-teal-700 font-bold">
                Rata-rata {{ round($nilaiList->avg('nilai'), 1) }}
            </span>
        </div>

        <div class="divide-y divide-gray-50">
            @foreach($nilaiList as $nilai)
            <div class="px-4 py-2.5 flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-sm text-gray-700 truncate">{{ $nilai->mataPelajaran?->nama_mapel ?? '—' }}</p>
                    @if($nilai->catatan)
                    <p class="text-xs text-gray-400 italic truncate">{{ $nilai->catatan }}</p>
                    @endif
                </div>
                <span class="text-sm font-bold px-2.5 py-0.5 rounded-full bg-gray-100 text-gray-800 shrink-0">
                    {{ $nilai->nilai }}
                </span>
            </div>
            @endforeach
        </div>
    </div>
    @empty
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-4 py-10 text-center">
        <p class="text-3xl mb-2">📚</p>
        <p class="text-sm font-medium text-gray-600">Belum ada data rapor akademik</p>
        <p class="text-xs text-gray-400 mt-1">Rapor akademik untuk tahun {{ $tahunAjaran }} belum tersedia.</p>
    </div>
    @endforelse

    @endif {{-- /akademik tab --}}

    {{-- ── Tombol Export PDF ────────────────────────────────────────────── --}}
    @if($santriId)
    <a href="{{ route('wali.laporan.pdf', [
            'santri_id'    => $santriId,
            'tahun_ajaran' => $tahunAjaran,
            'periode'      => \App\Services\TahunAjaranOptions::currentPeriode(),
        ]) }}"
       class="flex items-center justify-center gap-2 w-full py-3 px-4
              bg-green-700 text-white rounded-xl font-medium text-sm
              hover:bg-green-800 active:scale-[0.98] transition-all">
        ⬇ Unduh Laporan PDF
    </a>
    @endif

</div>
@endsection
