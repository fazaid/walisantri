{{-- File: resources/views/filament/admin/session-expired-handler.blade.php --}}
{{--
    Handler tunggal untuk semua kegagalan request Livewire di panel admin. Menggantikan
    handler error notification bawaan Filament (dimatikan via ->errorNotifications(false)
    di AdminPanelProvider), karena onFailure bawaan Filament SELALU memunculkan toast
    generik untuk kegagalan jaringan tanpa bisa dicegah.

    Perilaku:
    - 419 (session/CSRF kedaluwarsa) → redirect ke halaman login. Tanpa ini, Livewire
      menampilkan confirm() browser lalu diam-diam mengabaikan 419 berikutnya sehingga
      panel terlihat "nyangkut" sampai admin logout manual.
    - 403 (otorisasi; 2026-07-12) → reload penuh. Tab admin yang idle lebih lama dari
      SESSION_LIFETIME (120 menit) membuat sesi kedaluwarsa di server; satu-satunya yang
      mendeteksi ini di endpoint Livewire adalah
      Filament\Pages\Concerns\CanAuthorizeAccess::hydrateCanAuthorizeAccess() yang cuma
      abort_unless(canAccess(), 403) polos tanpa redirect. Reload (bukan langsung redirect
      ke login) supaya kalau ternyata 403 otorisasi asli — bukan sesi expired — user tetap
      dapat halaman error jelas + tombol Logout (resources/views/errors/minimal.blade.php),
      bukan reload ke /login padahal masih login.
    - Error server lain (500, dst) → tampilkan toast danger (menggantikan toast bawaan
      Filament yang kita matikan) supaya user tetap tahu ada masalah.
    - Kegagalan jaringan/transport (onFailure; mis. laptop di-sleep lalu dibuka sebelum
      WiFi pulih) → SENGAJA DIAM. Ini transient dan pulih sendiri di poll/interaksi
      berikutnya; menampilkan toast di sini cuma membingungkan user.
--}}
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.interceptRequest(({ onError, onFailure }) => {
            // Server membalas dengan respons HTTP error.
            onError(({ response, preventDefault }) => {
                const status = response?.status

                if (status === 419) {
                    preventDefault();
                    window.location.href = @json(route('login'));
                    return;
                }

                if (status === 403) {
                    preventDefault();
                    window.location.reload();
                    return;
                }

                // Error server asli (500, dsb).
                preventDefault();
                new FilamentNotification()
                    .title(@json(__('filament-panels::error-notifications.title')))
                    .body(@json(__('filament-panels::error-notifications.body')))
                    .danger()
                    .send();
            });

            // Kegagalan jaringan/transport (tidak ada respons HTTP sama sekali).
            // Diam — biarkan poll/interaksi berikutnya pulih sendiri.
            onFailure(() => {});
        });
    });
</script>
