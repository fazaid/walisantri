{{-- Tombol ganti mode. Butuh partials/tema di <head> halaman yang memuatnya.

     Boleh dipakai lebih dari sekali di satu halaman: partials/tema menyetel
     aria-pressed lewat querySelectorAll, jadi semua instans ikut diperbarui.

     $kelasTambahan untuk mengatur di lebar mana ia tampil.
     $kelasWarna MENGGANTIKAN warna bawaan — dipakai portal wali yang menaruhnya di
     header teal. Menumpuk warna lewat $kelasTambahan tidak bisa diandalkan: dua
     utilitas warna di layer yang sama dimenangkan urutan di berkas CSS, bukan
     urutan penulisan di atribut class. --}}
@php($kelasWarna = $kelasWarna ?? 'text-gray-500 hover:text-teal-700 hover:bg-gray-50')
<button type="button" data-tema-tombol onclick="gantiTema()" aria-pressed="false"
        title="Ganti mode terang/gelap" aria-label="Ganti mode terang/gelap"
        class="p-2 rounded-lg transition-colors shrink-0 cursor-pointer {{ $kelasWarna }} {{ $kelasTambahan ?? '' }}">
    {{-- Ikonnya menawarkan tujuan, bukan keadaan sekarang: bulan saat mode terang,
         matahari saat mode gelap. --}}
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" class="w-5 h-5 dark:hidden" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/>
    </svg>
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" class="w-5 h-5 hidden dark:block" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/>
    </svg>
</button>
