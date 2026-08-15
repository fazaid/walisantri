{{-- resources/views/wali/izin/index.blade.php --}}
@extends('wali.layouts.app')

@section('title', 'Pengajuan Izin')
@section('subtitle', 'Ajukan izin untuk anak Anda')
@section('back_url', route('wali.dashboard'))

@section('content')
<div class="space-y-4">

    @if(session('sukses_izin'))
    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
        <p class="text-sm font-medium text-emerald-800">Pengajuan terkirim.</p>
        <p class="text-xs text-emerald-700 mt-0.5">
            Menunggu persetujuan dari pesantren. Statusnya bisa Anda pantau di daftar bawah.
        </p>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         SECTION: Form pengajuan
    ══════════════════════════════════════════════════ --}}
    @if($bolehMengajukan)
    <div class="bg-white rounded-2xl shadow-sm border border-emerald-100 p-4">
        <p class="font-semibold text-gray-800 text-sm mb-3">Ajukan Izin Baru</p>

        @if($errors->any())
        <div class="mb-3 rounded-xl border border-red-200 bg-red-50 px-3 py-2">
            @foreach($errors->all() as $pesan)
                <p class="text-xs text-red-700">{{ $pesan }}</p>
            @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('wali.izin.store') }}" enctype="multipart/form-data" class="space-y-3">
            @csrf

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Anak</label>
                <select name="santri_id" required
                        class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                    @foreach($anak as $a)
                        <option value="{{ $a->id }}" @selected(old('santri_id') == $a->id)>{{ $a->nama_lengkap }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Jenis Izin</label>
                <select name="jenis" required
                        class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                    @foreach($jenisOptions as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(old('jenis') === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" required value="{{ old('tanggal_mulai') }}"
                           class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" required value="{{ old('tanggal_selesai') }}"
                           class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Alasan</label>
                <textarea name="alasan" rows="3" required maxlength="1000"
                          placeholder="Contoh: demam sejak semalam, sudah diperiksa dokter."
                          class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none">{{ old('alasan') }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Lampiran (opsional)</label>
                <input type="file" name="lampiran" accept="image/*"
                       class="w-full text-xs text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-emerald-700">
                <p class="text-xs text-gray-400 mt-1">Foto surat dokter atau surat keterangan. Maksimal 5 MB.</p>
            </div>

            <button type="submit"
                    class="w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">
                Kirim Pengajuan
            </button>
        </form>
    </div>
    @else
    {{-- Sesi Magic Link sengaja baca-saja (VerifyMagicToken menolak semua non-GET).
         Menyembunyikan formnya dengan penjelasan jauh lebih baik daripada
         membiarkan wali menulis alasan panjang lalu ditolak 403 tanpa sebab. --}}
    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
        <p class="text-sm font-medium text-amber-800">Pengajuan izin belum bisa dilakukan dari sini.</p>
        <p class="text-xs text-amber-700 mt-1">
            Anda membuka portal lewat tautan cepat, yang hanya bisa membaca data. Silakan masuk lewat halaman
            login dengan akun Anda untuk mengajukan izin — atau hubungi pesantren secara langsung.
        </p>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         SECTION: Riwayat pengajuan
    ══════════════════════════════════════════════════ --}}
    <div>
        <p class="font-semibold text-gray-800 text-sm mb-2">Riwayat Pengajuan</p>

        @forelse($daftar as $izin)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 px-4 py-3 mb-2">
            <div class="flex items-start justify-between gap-2">
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-gray-800 text-sm">{{ $izin->santri?->nama_lengkap ?? '—' }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ $izin->jenis->label() }} ·
                        {{ $izin->tanggal_mulai->translatedFormat('d M Y') }}
                        @if(! $izin->tanggal_mulai->isSameDay($izin->tanggal_selesai))
                            – {{ $izin->tanggal_selesai->translatedFormat('d M Y') }}
                        @endif
                    </p>
                </div>
                @php
                    $warna = match($izin->status->value) {
                        'disetujui' => 'bg-emerald-100 text-emerald-700',
                        'ditolak' => 'bg-red-100 text-red-700',
                        'dibatalkan' => 'bg-gray-100 text-gray-600',
                        default => 'bg-amber-100 text-amber-700',
                    };
                @endphp
                <span class="flex-shrink-0 rounded-full px-2.5 py-1 text-xs font-medium {{ $warna }}">
                    {{ $izin->status->label() }}
                </span>
            </div>

            <p class="text-xs text-gray-600 mt-2">{{ $izin->alasan }}</p>

            @if($izin->catatan_petugas)
            <p class="text-xs text-gray-500 mt-1.5 border-t border-gray-100 pt-1.5">
                <span class="font-medium">Catatan pesantren:</span> {{ $izin->catatan_petugas }}
            </p>
            @endif

            @if($izin->lampiran)
            <a href="{{ route('wali.izin.lampiran', $izin) }}" target="_blank"
               class="text-xs text-emerald-600 hover:underline mt-1.5 inline-block">Lihat lampiran →</a>
            @endif
        </div>
        @empty
        <div class="bg-white rounded-2xl border border-gray-100 py-10 text-center text-sm text-gray-400">
            Belum ada pengajuan izin.
        </div>
        @endforelse
    </div>

</div>
@endsection
