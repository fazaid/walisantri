{{--
    Tautan profil publik pesantren di topbar panel — menggantikan kolom pencarian
    global bawaan Filament (dimatikan di AdminPanelProvider).

    Kenapa menggantikan: pencarian itu tidak pernah dikurasi — dari 23 resource yang
    ikut terindeks, lima dicari lewat kolom yang tak berarti bagi manusia (id,
    tanggal, jam ke), sehingga mengetik angka memunculkan hasil sampah. Keputusan
    pemilik produk: ditukar dengan pintu ke halaman yang justru sering dibuka
    pengurus — profil publik pesantrennya sendiri.

    Merender KOSONG untuk super admin: ia tidak terikat satu pesantren, jadi tidak
    ada profil yang bisa ditunjuk.
--}}
@php
    $pesantren = auth()->user()?->pesantren;
@endphp

@if($pesantren)
    <a href="{{ $pesantren->url('/') }}"
       target="_blank"
       rel="noopener"
       class="fi-topbar-item flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-sm font-medium text-gray-600 outline-none transition duration-75 hover:bg-gray-100 focus-visible:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5 dark:focus-visible:bg-white/5"
       title="Buka halaman profil publik pesantren di tab baru">
        <span class="hidden sm:inline">Profil Pesantren</span>

        {{-- Ikon "buka di tab baru" ditaruh SESUDAH teks: ia keterangan atas
             perilaku tautannya, bukan lambang tujuannya. --}}
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="h-5 w-5" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
        </svg>
    </a>
@endif
