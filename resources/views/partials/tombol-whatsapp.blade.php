{{--
    Tombol WhatsApp melayang di pojok kanan bawah halaman situs — pintu tercepat
    ke tim Walisantri tanpa mengisi formulir.

    Dipasang di empat halaman milik platform: landing, /harga, /panduan, dan
    /demo. SENGAJA TIDAK dipasang di halaman profil publik pesantren: pengunjung
    di sana calon wali yang mencari PESANTRENNYA, dan nomor ini akan mengalihkan
    mereka ke vendor. Halaman itu sudah menampilkan nomor telepon pesantrennya
    sendiri.

    Nomornya setelan platform (Merek & Kontak, super admin), sama dengan tombol
    Bantuan di panel dan tombol Hubungi Kami di /harga. Merender KOSONG bila
    keduanya belum diisi — lebih baik tidak ada daripada menuju tautan rusak.

    Nomor CS dipakai sebagai cadangan, meniru HargaController: hanya yang kedua
    punya nilai bawaan dari migrasi, jadi tanpa cadangan itu tombol ini tidak
    akan pernah muncul di instalasi yang belum pernah mengisi wa_dukungan.

    Tidak ikut digerbangi status pendaftaran/demo, berbeda dari tombol di
    /harga. Yang di sana ajakan berlangganan paket, yang ini kontak dukungan
    umum: orang tetap boleh bertanya meski pendaftaran sedang ditutup.

    $pesan boleh diisi halaman pemanggil untuk menyebut konteksnya.

    JEBAKAN WARNA — dibaca sebelum menyunting kelasnya. Mode gelap halaman situs
    bekerja dengan MEMBALIK variabel palet (lihat resources/css/app.css), jadi
    `text-white` berubah jadi #0b1220 dan `bg-green-*` ikut bergeser. Hijau
    WhatsApp harus tetap hijau di kedua mode, karena itulah yang membuatnya
    dikenali seketika — maka ia ditulis sebagai nilai literal, bukan lewat palet.
--}}
@php
    $waDukungan = \App\Models\PlatformBrandingSetting::waDukungan()
        ?? \App\Models\PlatformContactSetting::csWhatsapp();

    $pesan ??= 'Halo tim Walisantri, saya ingin bertanya tentang Walisantri untuk pesantren kami.';
@endphp

@if ($waDukungan)
    {{-- z-30: di bawah drawer sidebar /panduan (z-40) dan lapis gelapnya (z-35),
         supaya tombol ini ikut teredam saat menu mobile halaman itu dibuka. --}}
    <a href="https://wa.me/{{ $waDukungan }}?text={{ rawurlencode($pesan) }}"
       target="_blank"
       rel="noopener"
       aria-label="Hubungi tim Walisantri lewat WhatsApp"
       title="Hubungi tim Walisantri lewat WhatsApp"
       class="fixed bottom-5 right-5 z-30 flex items-center gap-2 rounded-full bg-[#25D366] px-4 py-3 text-[#ffffff] shadow-lg transition-transform duration-150 hover:scale-105 hover:bg-[#1FB055] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#25D366]">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 shrink-0" aria-hidden="true">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884a9.82 9.82 0 0 1 6.988 2.896 9.83 9.83 0 0 1 2.893 6.994c-.003 5.45-4.437 9.885-9.885 9.885m8.413-18.297A11.82 11.82 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.88 11.88 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.82 11.82 0 0 0-3.48-8.413"/>
        </svg>

        <span class="hidden pe-1 text-sm font-semibold sm:inline">Hubungi Kami</span>
    </a>
@endif
