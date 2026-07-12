{{-- File: resources/views/filament/admin/session-expired-handler.blade.php --}}
{{--
    Tanpa ini, Livewire menampilkan confirm() browser saat request gagal 419
    (session/CSRF expired), lalu diam-diam mengabaikan error 419 berikutnya
    sehingga panel terlihat "nyangkut" sampai admin logout manual.
    Redirect otomatis ke halaman login memberi jalan keluar yang jelas.
--}}
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.hook('request', ({ fail }) => {
            fail(({ status, preventDefault }) => {
                if (status === 419) {
                    preventDefault();
                    window.location.href = @json(route('login'));
                }
            });
        });
    });
</script>
