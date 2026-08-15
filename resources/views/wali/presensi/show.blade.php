@extends('wali.layouts.app')

@section('title', 'Presensi Santri')
@section('subtitle', $santri->nama_lengkap)
@section('back_url', route('wali.santri.show', $santri->id))

@php
    // Enum StatusKehadiran mengembalikan nama warna Filament (success/warning/…),
    // sedangkan portal wali memakai Tailwind murni tanpa Filament. Pemetaannya
    // ditaruh di sini, bukan di enum: enum itu dipakai panel admin, dan
    // menambahkan kelas Tailwind ke sana akan mencampur dua sistem tampilan.
    $warnaBadge = [
        'success' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'warning' => 'bg-amber-50 text-amber-700 border-amber-200',
        'danger' => 'bg-rose-50 text-rose-700 border-rose-200',
        'info' => 'bg-sky-50 text-sky-700 border-sky-200',
    ];
@endphp

@section('content')
<div class="space-y-4">

    {{-- Pemilih bulan --}}
    <form method="GET" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-3">
        <label for="bulan" class="block text-xs font-medium text-gray-500 mb-1.5">Bulan</label>
        <select name="bulan" id="bulan" onchange="this.form.submit()"
                class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
            @foreach($bulanOptions as $opsi)
                <option value="{{ $opsi['key'] }}" @selected($opsi['key'] === $bulan)>{{ $opsi['label'] }}</option>
            @endforeach
        </select>
        <noscript>
            <button type="submit" class="mt-2 w-full rounded-xl bg-teal-600 px-3 py-2 text-sm font-medium text-white">Tampilkan</button>
        </noscript>
    </form>

    @if($ringkasan)
        {{-- Kartu utama: persentase kehadiran --}}
        <div class="bg-teal-50 border border-teal-200 rounded-2xl p-4">
            <div class="flex items-center gap-3">
                <span class="text-3xl">🗓️</span>
                <div>
                    <p class="text-2xl font-bold text-teal-700 leading-tight">{{ $ringkasan->persen_kehadiran }}%</p>
                    <p class="text-xs text-teal-600">
                        kehadiran — {{ $ringkasan->hadir_efektif }} dari {{ $ringkasan->hari_efektif }} hari efektif
                    </p>
                </div>
            </div>
            <p class="mt-3 text-xs text-teal-700/80 leading-relaxed">
                Hari efektif tidak menghitung hari libur pondok. Hadir, Terlambat, dan Dispensasi sama-sama
                dihitung hadir — santrinya ada di tempat.
            </p>
        </div>

        @if($ringkasan->tanpa_keterangan > 0)
            {{--
                "Tanpa Keterangan" BUKAN Alpa, dan bedanya wajib dijelaskan di sini.
                Sistem tidak pernah menandai Alpa otomatis (§11): angka ini berarti
                hari efektif yang catatannya belum diisi — bisa karena ustadz belum
                sempat, bukan karena anaknya tidak masuk. Tanpa kalimat ini wali
                akan membacanya sebagai tuduhan.
            --}}
            <div class="bg-gray-50 border border-gray-200 rounded-2xl p-4">
                <p class="text-sm font-medium text-gray-700">
                    {{ $ringkasan->tanpa_keterangan }} hari belum ada catatan
                </p>
                <p class="mt-1 text-xs text-gray-500 leading-relaxed">
                    Hari efektif yang presensinya belum diisi pondok. Ini bukan berarti tidak hadir —
                    tanyakan ke wali kelas bila angkanya terus bertambah.
                </p>
            </div>
        @endif

        {{-- Hitungan per status --}}
        <div class="grid grid-cols-2 gap-2">
            @foreach($hitungan as $item)
                <div class="rounded-2xl border p-3 {{ $warnaBadge[$item['warna']] ?? 'bg-gray-50 text-gray-700 border-gray-200' }}">
                    <p class="text-xl font-bold leading-tight">{{ $item['jumlah'] }}</p>
                    <p class="text-xs opacity-80">{{ $item['label'] }}</p>
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-gray-50 border border-gray-200 rounded-2xl p-4 text-center">
            <p class="text-sm text-gray-600">Rekap kehadiran belum tersedia untuk santri ini.</p>
            <p class="mt-1 text-xs text-gray-500">Hubungi pondok bila data santri sudah tidak aktif.</p>
        </div>
    @endif

    {{-- Daftar harian --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100">
            <p class="text-sm font-semibold text-gray-800">Catatan Harian</p>
        </div>

        @forelse($harian as $baris)
            <div class="px-4 py-3 border-b border-gray-50 last:border-0">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800">
                            {{ $baris['tanggal']->translatedFormat('l, d M Y') }}
                        </p>
                        @if($baris['libur'])
                            <p class="text-xs text-amber-600 mt-0.5">Hari libur: {{ $baris['libur'] }}</p>
                        @endif
                        @if($baris['catatan'])
                            <p class="text-xs text-gray-500 mt-0.5">{{ $baris['catatan'] }}</p>
                        @endif
                    </div>
                    <span class="flex-shrink-0 text-xs px-2 py-0.5 rounded-full font-medium border {{ $warnaBadge[$baris['status']->color()] ?? 'bg-gray-50 text-gray-700 border-gray-200' }}">
                        {{ $baris['status']->label() }}
                    </span>
                </div>
            </div>
        @empty
            <div class="px-4 py-10 text-center">
                <p class="text-sm text-gray-400">Belum ada catatan presensi pada bulan ini.</p>
            </div>
        @endforelse
    </div>

    <p class="px-1 text-xs text-gray-400 leading-relaxed">
        Halaman ini baca-saja. Untuk mengajukan izin, sakit, atau pulang, gunakan menu
        <a href="{{ route('wali.izin') }}" class="text-teal-600 font-medium">Pengajuan Izin</a>.
    </p>

</div>
@endsection
