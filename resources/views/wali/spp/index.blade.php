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

    {{-- Info rekening --}}
    @if(count($rekening) > 0)
        <div class="bg-white border border-gray-100 rounded-2xl px-4 py-4 mb-5">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Transfer Pembayaran SPP ke:</p>
            <div class="space-y-2">
                @foreach($rekening as $rek)
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-gray-800 text-sm">{{ $rek['nama_bank'] }}</p>
                            <p class="text-xs text-gray-400">{{ $rek['atas_nama'] }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-mono font-semibold text-gray-800 text-sm tracking-wide">
                                {{ $rek['nomor_rekening'] }}
                            </p>
                        </div>
                    </div>
                    @if(! $loop->last)
                        <hr class="border-gray-100">
                    @endif
                @endforeach
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
                        @php
                            $borderClass = $tagihan->isLunas()
                                ? 'border-gray-100'
                                : ($tagihan->isMenungguKonfirmasi() ? 'border-orange-200' : 'border-red-100');
                            $sukses = session('sukses_tagihan') == $tagihan->id;
                        @endphp
                        <div class="bg-white border {{ $borderClass }} rounded-xl overflow-hidden">
                            {{-- Baris utama --}}
                            <div class="px-4 py-3 flex items-center justify-between">
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
                                    @elseif($tagihan->isMenungguKonfirmasi())
                                        <p class="text-xs text-orange-500 mt-0.5">
                                            Konfirmasi dikirim {{ $tagihan->dikonfirmasi_wali_at->diffForHumans() }}
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
                                        {{ $tagihan->isLunas() ? 'bg-green-100 text-green-700' :
                                          ($tagihan->isMenungguKonfirmasi() ? 'bg-orange-100 text-orange-700' : 'bg-red-100 text-red-700') }}">
                                        {{ $tagihan->status->label() }}
                                    </span>
                                </div>
                            </div>

                            {{-- Tombol & form konfirmasi (hanya untuk belum_bayar) --}}
                            @if($tagihan->status === \App\Enums\StatusTagihanSpp::BelumBayar)
                                @if($sukses)
                                    <div class="px-4 py-2 bg-green-50 border-t border-green-100">
                                        <p class="text-xs text-green-700 font-medium">✓ Bukti transfer berhasil dikirim. Menunggu konfirmasi admin.</p>
                                    </div>
                                @else
                                    <div class="border-t border-gray-50">
                                        <button type="button"
                                                onclick="toggleForm('form-{{ $tagihan->id }}')"
                                                class="w-full text-center text-xs font-medium text-teal-600 py-2.5 hover:bg-teal-50 transition-colors">
                                            💳 Saya Sudah Transfer — Kirim Bukti
                                        </button>
                                        <div id="form-{{ $tagihan->id }}" class="hidden px-4 pb-4">
                                            <form method="POST"
                                                  action="{{ route('wali.spp.konfirmasi', $tagihan->id) }}"
                                                  enctype="multipart/form-data"
                                                  class="space-y-3">
                                                @csrf
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-600 mb-1">
                                                        Foto / Screenshot Bukti Transfer
                                                    </label>
                                                    <input type="file"
                                                           name="bukti_transfer"
                                                           accept="image/*"
                                                           capture="environment"
                                                           class="w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
                                                    @error('bukti_transfer')
                                                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                                <button type="submit"
                                                        class="w-full bg-teal-700 text-white text-xs font-semibold py-2 rounded-lg hover:bg-teal-800 transition-colors">
                                                    Kirim Konfirmasi
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                            @endif
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

<script>
function toggleForm(id) {
    document.getElementById(id).classList.toggle('hidden');
}
</script>

@endsection
