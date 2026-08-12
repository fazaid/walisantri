{{--
    Tombol copyable() bawaan Filament memanggil navigator.clipboard.writeText()
    tanpa fallback. API itu cuma tersedia di secure context (HTTPS / localhost),
    jadi lewat http://<host> penyalinan gagal diam-diam — termasuk kolom
    "Link Wali" di daftar santri.

    Polyfill ini menyamakan perilakunya dengan tombol Salin di modal Link Wali,
    yang sejak awal punya fallback document.execCommand('copy').
--}}
<script>
    (function () {
        if (window.navigator.clipboard && window.isSecureContext) {
            return;
        }

        var writeText = function (text) {
            var textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'fixed';
            textarea.style.top = '-9999px';
            document.body.appendChild(textarea);

            // Simpan seleksi pengguna supaya tidak hilang setelah menyalin.
            var selection = document.getSelection();
            var sebelumnya = selection && selection.rangeCount > 0 ? selection.getRangeAt(0) : null;

            textarea.select();
            textarea.setSelectionRange(0, 99999);

            var berhasil = false;

            try {
                berhasil = document.execCommand('copy');
            } catch (e) {
                berhasil = false;
            }

            document.body.removeChild(textarea);

            if (sebelumnya) {
                selection.removeAllRanges();
                selection.addRange(sebelumnya);
            }

            return berhasil
                ? Promise.resolve()
                : Promise.reject(new Error('Penyalinan ke clipboard tidak didukung browser ini.'));
        };

        try {
            Object.defineProperty(window.navigator, 'clipboard', {
                value: { writeText: writeText },
                configurable: true,
            });
        } catch (e) {
            // Sebagian browser mengunci properti ini — biarkan perilaku bawaan.
        }
    })();
</script>
