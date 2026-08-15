import { Html5Qrcode } from 'html5-qrcode';

/**
 * Pemindaian kartu presensi lewat kamera.
 *
 * Ini LAPIS KEDUA. Jalur utamanya tetap kolom teks ber-autofocus di halaman yang
 * sama — alat pemindai USB/Bluetooth berperilaku sebagai papan ketik, jadi ia
 * bekerja tanpa JavaScript sama sekali dan bisa diuji penuh di PHPUnit. Kamera
 * ditambahkan untuk pesantren yang tidak punya alat pemindai: cukup ponsel atau
 * laptop ber-webcam.
 *
 * ⚠️ Hanya memakai html5-qrcode, TIDAK ada jalur `BarcodeDetector` bawaan browser.
 * Rancangan awal menyebut "pakai BarcodeDetector bila ada, jatuh ke pustaka bila
 * tidak" demi menghemat unduhan di Chrome. Itu dilepas dengan sadar: Safari/iOS
 * tidak mendukung BarcodeDetector sama sekali (semua browser di iOS memakai
 * WebKit), jadi pustakanya tetap harus ada — dan dua jalur kode untuk halaman yang
 * TIDAK BISA disentuh test suite adalah pertukaran yang buruk.
 */
window.presensiScanner = function () {
    return {
        /**
         * Dua bendera, bukan satu, dan itu memperbaiki bug nyata.
         *
         * `tampil` mengendalikan wadah video; `aktif` menandai kamera benar-benar
         * berjalan. Versi pertama memakai satu bendera yang baru dinyalakan SETELAH
         * start() berhasil — akibatnya wadahnya masih display:none saat
         * html5-qrcode mengukurnya, clientWidth terbaca 0, dan videonya tidak
         * pernah muncul. Wadah harus terlihat LEBIH DULU.
         */
        tampil: false,
        aktif: false,
        memuat: false,
        galat: '',
        pemindai: null,

        /** kode => timestamp pemindaian terakhir, untuk menelan pembacaan beruntun */
        terakhir: new Map(),

        /**
         * getUserMedia hanya tersedia di secure context. Di localhost peramban
         * memberi keringanan, tapi domain .test lewat http biasa TIDAK — dan
         * gejalanya cuma `navigator.mediaDevices === undefined`, yang tanpa
         * penjelasan terbaca seperti "kameranya rusak".
         */
        get didukung() {
            return !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
        },

        async nyalakan() {
            this.galat = '';

            if (!this.didukung) {
                this.galat = window.isSecureContext
                    ? 'Peramban ini tidak mendukung akses kamera. Gunakan kolom di atas untuk memindai dengan alat pemindai, atau ketik kode kartu.'
                    : 'Kamera hanya bisa dipakai lewat koneksi aman (https). Buka halaman ini dengan https, atau gunakan alat pemindai/ketik kode kartu.';

                return;
            }

            this.memuat = true;

            // Tampilkan wadahnya dulu, lalu tunggu Alpine benar-benar menerapkan
            // perubahan DOM-nya. html5-qrcode menghitung ukuran video dan kotak
            // pindai dari lebar elemen; elemen yang masih display:none berukuran
            // nol, dan videonya tidak akan pernah terlihat.
            this.tampil = true;
            await this.$nextTick();

            try {
                this.pemindai = new Html5Qrcode('pemindai-kamera', { verbose: false });

                await this.pemindai.start(
                    // Kamera belakang untuk ponsel; di laptop berwebcam tunggal
                    // peramban mengabaikan preferensi ini dan memakai apa adanya.
                    { facingMode: 'environment' },
                    {
                        fps: 10,
                        // Kotak pindai diturunkan dari ukuran viewfinder yang
                        // sebenarnya, bukan angka tetap: kotak yang lebih besar
                        // daripada videonya membuat pustaka ini menolak memulai.
                        qrbox: (lebar, tinggi) => {
                            const sisi = Math.floor(Math.min(lebar, tinggi) * 0.7);

                            return { width: sisi, height: sisi };
                        },
                    },
                    (teks) => this.terbaca(teks),
                    // Callback kegagalan per-frame sengaja dibiarkan kosong: ia
                    // menyala puluhan kali per detik selama tidak ada QR di depan
                    // kamera, dan itu keadaan normal, bukan galat.
                    () => {},
                );

                this.aktif = true;
            } catch (e) {
                // Sebutkan penyebab aslinya. Pesan generik "pastikan izin
                // diberikan" menyesatkan saat penyebabnya sebenarnya kamera
                // dipakai aplikasi lain, atau perangkatnya memang tidak punya.
                this.galat = 'Kamera tidak bisa dibuka: ' + (e?.message || e)
                    + ' — pastikan izin kamera diberikan untuk situs ini dan tidak sedang dipakai aplikasi lain.';

                await this.bersihkanPemindai();
                this.tampil = false;
            } finally {
                this.memuat = false;
            }
        },

        async matikan() {
            await this.bersihkanPemindai();
            this.tampil = false;
        },

        async bersihkanPemindai() {
            if (!this.pemindai) {
                this.aktif = false;

                return;
            }

            try {
                await this.pemindai.stop();
                this.pemindai.clear();
            } catch (e) {
                // Sudah berhenti duluan (tab berpindah, izin dicabut) — tidak ada
                // yang perlu dilaporkan ke pengguna.
            }

            this.pemindai = null;
            this.aktif = false;
        },

        terbaca(teks) {
            const kode = String(teks).trim();

            if (kode === '') {
                return;
            }

            // Kamera membaca QR yang sama puluhan kali per detik selama kartu masih
            // di depan lensa. Tanpa jeda ini, satu kartu menghasilkan puluhan
            // request Livewire — dan server tetap menjawab benar ("sudah tercatat")
            // setiap kali, jadi gejalanya cuma layar yang membanjir.
            const sekarang = Date.now();
            const sebelumnya = this.terakhir.get(kode);

            if (sebelumnya && sekarang - sebelumnya < 3000) {
                return;
            }

            this.terakhir.set(kode, sekarang);

            // Masuk lewat pintu yang SAMA dengan alat pemindai dan ketik manual:
            // seluruh aturan (terlambat, scan ganda, cakupan ustadz, tenant) hidup
            // di sisi server dan tidak perlu disalin ke sini.
            this.$wire.set('kode', kode).then(() => this.$wire.call('scan'));
        },

        /** Lepas kamera saat komponen dibongkar — jangan biarkan lampunya menyala. */
        bersihkan() {
            this.matikan();
        },
    };
};
