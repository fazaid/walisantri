{{--
    Subheading dasbor: tanggal Indonesia + jam WIB yang detiknya berjalan.

    Teks awal dirender server supaya sudah benar sejak paint pertama dan tetap
    terbaca kalau JS mati. Tick-nya memakai Intl dengan timeZone eksplisit, bukan
    jam lokal browser — pengguna yang perangkatnya di luar WIB tetap melihat jam
    yang sama dengan yang dipakai seluruh data platform.
--}}
<span
    x-data="{
        teks: @js($awal),
        pengulang: null,
        tanggal: new Intl.DateTimeFormat('id-ID', {
            timeZone: @js($zona),
            weekday: 'long',
            day: '2-digit',
            month: 'long',
            year: 'numeric',
        }),
        // Locale en-GB, bukan id-ID: pemisah jam versi Indonesia adalah titik
        // ('14.32.07') sehingga teks akan meloncat dari titik dua ke titik satu
        // detik setelah halaman dimuat, tidak sama dengan yang dirender server.
        jam: new Intl.DateTimeFormat('en-GB', {
            timeZone: @js($zona),
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false,
        }),
        perbarui() {
            const saat = new Date()

            this.teks = this.tanggal.format(saat) + ' · ' + this.jam.format(saat) + ' WIB'
        },
    }"
    x-init="
        perbarui()
        pengulang = setInterval(() => perbarui(), 1000)
    "
    x-on:destroy="clearInterval(pengulang)"
    x-text="teks"
>{{ $awal }}</span>
