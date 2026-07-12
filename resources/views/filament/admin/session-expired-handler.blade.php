{{-- File: resources/views/filament/admin/session-expired-handler.blade.php --}}
{{--
    Tanpa ini, Livewire menampilkan confirm() browser saat request gagal 419
    (session/CSRF expired), lalu diam-diam mengabaikan error 419 berikutnya
    sehingga panel terlihat "nyangkut" sampai admin logout manual.
    Redirect otomatis ke halaman login memberi jalan keluar yang jelas.

    403 ditambahkan (2026-07-12) karena penyebab berbeda tapi gejala serupa:
    tab admin yang dibiarkan idle lebih lama dari SESSION_LIFETIME (120 menit)
    membuat sesi kedaluwarsa di server, tapi tidak ada middleware auth kustom
    (FilamentAuthenticate/SaaSLifecycleLock/dkk) yang persistent di endpoint
    Livewire (/livewire-*/update) — satu-satunya yang mendeteksi ini adalah
    Filament\Pages\Concerns\CanAuthorizeAccess::hydrateCanAuthorizeAccess(),
    yang cuma abort_unless(canAccess(), 403) polos tanpa redirect, sehingga
    klik pertama setelah tab dibuka lagi menampilkan toast error generik
    Filament yang membingungkan. Reload penuh (bukan langsung redirect ke
    login seperti 419) supaya kalau ternyata bukan sesi expired tapi memang
    403 otorisasi asli, user tetap dapat halaman error yang jelas + tombol
    Logout (lihat resources/views/errors/minimal.blade.php), bukan reload
    ke /login padahal mereka masih login.
--}}
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.hook('request', ({ fail }) => {
            fail(({ status, preventDefault }) => {
                if (status === 419) {
                    preventDefault();
                    window.location.href = @json(route('login'));
                } else if (status === 403) {
                    preventDefault();
                    window.location.reload();
                }
            });
        });
    });
</script>
