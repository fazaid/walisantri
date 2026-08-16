{{--
    Tombol Bantuan di topbar panel — membuka percakapan WhatsApp dengan tim.

    Nomornya TIDAK di-hardcode: ia setelan platform (Merek & Kontak, super admin),
    supaya bisa diganti tanpa deploy. Merender KOSONG bila belum diisi — lebih baik
    tombolnya tidak ada daripada ada tapi menuju tautan rusak.

    Pesan pembuka diisi otomatis dengan nama pesantren dan peran pengirim, karena
    pertanyaan pertama tim dukungan selalu "ini dari pesantren mana".
--}}
@php
    $waDukungan = \App\Models\PlatformBrandingSetting::waDukungan();
    $pengguna = auth()->user();

    $pesan = $pengguna?->pesantren
        ? "Halo tim Walisantri, saya {$pengguna->name} dari {$pengguna->pesantren->nama_pesantren}. Saya butuh bantuan terkait "
        : 'Halo tim Walisantri, saya butuh bantuan terkait ';
@endphp

@if($waDukungan)
    <a href="https://wa.me/{{ $waDukungan }}?text={{ rawurlencode($pesan) }}"
       target="_blank"
       rel="noopener"
       class="fi-topbar-item flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-sm font-medium text-gray-600 outline-none transition duration-75 hover:bg-gray-100 focus-visible:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5 dark:focus-visible:bg-white/5"
       title="Hubungi tim Walisantri lewat WhatsApp">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="h-5 w-5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/>
        </svg>

        <span class="hidden sm:inline">Bantuan</span>
    </a>
@endif
