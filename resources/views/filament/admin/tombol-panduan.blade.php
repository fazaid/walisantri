{{--
    Tombol "Panduan" di baris judul setiap halaman panel — membuka modal berisi
    langkah singkat memakai menu yang sedang dibuka.

    Dipasang lewat SATU render hook PAGE_HEADER_ACTIONS_BEFORE, bukan lewat
    getHeaderActions() di tiap halaman: naskahnya terkumpul di satu tempat
    (App\Filament\Support\Panduan) dan menu baru cukup menambah satu entri di
    sana, tanpa menyentuh berkas ini maupun providernya.

    $scopes dikirim Filament sendiri ke closure hook — lihat Panduan::untukScope().
    Berkas ini KHUSUS untuk konteks render Livewire panel: komponen modal Filament
    memakai $this saat menyusun wire:key, jadi jangan dipakai ulang di email atau
    halaman publik.

    Merender KOSONG bila menu ini belum punya entri panduan — lebih baik
    tombolnya tidak ada daripada ada tapi membuka modal hampa.

    Beda dengan tombol "Bantuan" di topbar: yang itu menghubungi tim lewat
    WhatsApp, yang ini dokumentasi. Keduanya tampak di layar yang sama, jadi
    ikonnya sengaja dibedakan: buku untuk dokumentasi, balon chat untuk Bantuan.
    JANGAN memakai ikon tanda tanya di sini — itu makna "minta tolong", yang
    sudah dipegang tombol Bantuan.
--}}
@php
    $panduan = \App\Filament\Support\Panduan::untukScope($scopes ?? []);
@endphp

@if ($panduan)
    @php
        // Batasan role dirender hanya untuk yang terkena — admin tidak perlu
        // membaca aturan yang tidak berlaku baginya.
        $catatanUstadz = auth()->user()?->role === 'ustadz'
            ? ($panduan['ustadz'] ?? null)
            : null;
    @endphp

    <x-filament::modal
        id="panduan-halaman"
        icon="heroicon-o-book-open"
        icon-color="primary"
        width="lg"
        :heading="$panduan['judul']"
        :description="$panduan['ringkas']"
    >
        <x-slot name="trigger">
            {{-- labeled-from: di bawah breakpoint sm komponen beralih sendiri ke
                 icon-button, jadi labelnya menghilang tanpa mengecilkan sasaran
                 sentuh di HP. --}}
            <x-filament::button
                color="gray"
                icon="heroicon-o-book-open"
                labeled-from="sm"
                tooltip="Panduan singkat memakai menu ini"
            >
                Panduan
            </x-filament::button>
        </x-slot>

        <ol class="list-decimal space-y-2.5 ps-5 text-sm/6 text-gray-600 marker:font-semibold marker:text-gray-400 dark:text-gray-400 dark:marker:text-gray-500">
            @foreach ($panduan['langkah'] as $langkah)
                {{-- Naskahnya konstanta di Panduan::PETA, bukan masukan pengguna:
                     <strong>/<code> di dalamnya memang dimaksudkan tampil. --}}
                <li class="ps-1">{!! $langkah !!}</li>
            @endforeach
        </ol>

        @if (filled($catatanUstadz))
            <div class="mt-4 rounded-lg bg-teal-50 px-3 py-2.5 text-sm/6 text-teal-800 dark:bg-teal-400/10 dark:text-teal-300">
                <span class="font-semibold">Untuk Ustadz</span> — {{ $catatanUstadz }}
            </div>
        @endif

        @if (filled($panduan['catatan'] ?? null))
            <div class="mt-3 rounded-lg bg-amber-50 px-3 py-2.5 text-sm/6 text-amber-800 dark:bg-amber-400/10 dark:text-amber-300">
                <span class="font-semibold">Perhatikan</span> — {{ $panduan['catatan'] }}
            </div>
        @endif

        <x-slot name="footerActions">
                @if (filled($panduan['anchor'] ?? null))
                    {{-- Tab baru: pengurus sering membuka ini di tengah mengisi
                         form, dan meninggalkan halaman akan membuang isiannya. --}}
                    <x-filament::button
                        tag="a"
                        href="{{ route('panduan') }}#{{ $panduan['anchor'] }}"
                        target="_blank"
                        rel="noopener"
                        color="gray"
                        icon="heroicon-m-arrow-top-right-on-square"
                        icon-position="after"
                    >
                        Baca panduan lengkap
                    </x-filament::button>
                @endif

                <x-filament::button
                    color="gray"
                    x-on:click="$dispatch('close-modal', { id: 'panduan-halaman' })"
                >
                    Tutup
                </x-filament::button>
        </x-slot>
    </x-filament::modal>
@endif
