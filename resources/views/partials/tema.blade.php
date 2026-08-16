{{--
    Pemilih mode terang/gelap untuk halaman publik.

    Wajib diletakkan di <head> dan tetap sinkron (bukan defer/module): kelasnya
    harus terpasang SEBELUM halaman dilukis, kalau tidak pembaca bermode gelap
    kena kedip putih setiap membuka halaman.

    Tanpa pilihan tersimpan, setelan perangkat yang dipakai. Menekan tombolnya
    menimpa itu, dan pilihannya diingat lintas halaman lewat localStorage —
    inilah satu-satunya alasan halaman ini tidak lagi bebas JavaScript.
--}}
<script>
    (function () {
        var KUNCI = 'tema';
        var akar = document.documentElement;

        function tersimpan() {
            try { return localStorage.getItem(KUNCI); } catch (e) { return null; }   // Safari private mode melempar
        }

        function terapkan(gelap) {
            akar.classList.toggle('dark', gelap);
            document.querySelectorAll('[data-tema-tombol]').forEach(function (tombol) {
                tombol.setAttribute('aria-pressed', gelap ? 'true' : 'false');
            });
        }

        var pilihan = tersimpan();
        terapkan(pilihan ? pilihan === 'gelap' : window.matchMedia('(prefers-color-scheme: dark)').matches);

        window.gantiTema = function () {
            var gelap = !akar.classList.contains('dark');
            terapkan(gelap);
            try { localStorage.setItem(KUNCI, gelap ? 'gelap' : 'terang'); } catch (e) {}
        };

        // Perangkat berpindah mode saat halaman terbuka hanya diikuti selama
        // pembaca belum pernah memilih sendiri — pilihan manual tidak ditimpa.
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
            if (! tersimpan()) {
                terapkan(e.matches);
            }
        });

        // Tombolnya dirender sebelum skrip ini sempat menyetel aria-pressed pada
        // sebagian halaman, jadi status awalnya disetel ulang setelah DOM siap.
        document.addEventListener('DOMContentLoaded', function () {
            terapkan(akar.classList.contains('dark'));
        });
    })();
</script>
