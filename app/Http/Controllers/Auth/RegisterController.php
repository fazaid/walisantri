<?php

namespace App\Http\Controllers\Auth;

use App\Enums\PaketLangganan;
use App\Http\Controllers\Controller;
use App\Mail\SambutanPendaftaran;
use App\Models\EmailSetting;
use App\Models\Pesantren;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\Wilayah;
use App\Rules\NomorWhatsApp;
use App\Rules\SlugNotReserved;
use App\Rules\ValidTenantSlug;
use App\Rules\WilayahJalurValid;
use App\Services\BillingCalculatorService;
use App\Services\FonnteWhatsAppService;
use App\Services\OnboardPesantren;
use App\Support\WilayahLookup;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    /**
     * Sesi magic link BUKAN "pengguna yang sudah login" di corong pendaftaran.
     *
     * Pengunjung yang mengetuk /coba benar-benar di-login-kan sebagai wali tenant
     * demo (`VerifyMagicToken` memanggil `Auth::login()`) — sifat read-only-nya
     * ditegakkan `BlockMagicLinkSession`, bukan oleh ketiadaan sesi. Kalau sesi itu
     * sampai terlihat di apex, calon pelanggan yang baru mencoba demo tidak akan
     * pernah bisa membuka form pendaftaran: ia dipantulkan ke portal demo.
     *
     * Hari ini yang menahannya cookie ber-scope host (§1.8 Fase 1) — tapi itu satu
     * variabel env dari rusak, dan `SESSION_DOMAIN=.walisantri.test` di lingkungan
     * lokal membuktikannya secara langsung. Pagar ini tidak bergantung pada scope
     * cookie sama sekali.
     */
    private function sedangMencobaDemo(): bool
    {
        return (bool) session('magic_link_session');
    }

    public function showForm(Request $request)
    {
        if (Auth::check() && ! $this->sedangMencobaDemo()) {
            return $this->redirectAuthenticated();
        }

        return view('auth.register', [
            'registrationOpen' => PlatformSetting::registrationOpen(),
            'demoOpen' => PlatformSetting::demoOpen(),
            // Paket pilihan dari kartu /harga. null = pendaftar datang lewat tombol
            // "Daftar" biasa dan memilih paketnya di langkah 1 form ini — /register
            // tetap berdiri sendiri, jadi tautan & bookmark lama tidak mati.
            'paketTerpilih' => $this->paketDariQuery($request),
            'paketPilihan' => $this->kartuPaket(),
            // Provinsi dirender server-side (38 baris, di-cache) supaya kolom pertama
            // kaskade sudah terisi sebelum JS jalan — sekaligus membuat halaman tetap
            // masuk akal saat JS mati.
            'provinsi' => Wilayah::provinsi(),
        ]);
    }

    /**
     * Paket dari query string — dipakai HANYA untuk memilih tampilan awal form.
     * Nilai yang benar-benar ditulis ke database selalu lewat validasi di store().
     */
    private function paketDariQuery(Request $request): ?PaketLangganan
    {
        $paket = PaketLangganan::tryFrom((string) $request->query('paket'));

        return $paket?->bisaDipilihSendiri() ? $paket : null;
    }

    /**
     * Kartu pilihan paket di langkah 1. Kuotanya dibaca lewat kalkulator — sumber
     * yang sama dengan /harga dan OnboardPesantren, supaya angka yang dijanjikan
     * form ini persis kuota yang nanti diterima tenant.
     *
     * @return list<array{nilai: string, nama: string, kuota: int}>
     */
    private function kartuPaket(): array
    {
        $kalkulator = app(BillingCalculatorService::class);

        return array_map(fn (PaketLangganan $paket) => [
            'nilai' => $paket->value,
            'nama' => $paket->label(),
            'kuota' => $kalkulator->hitungUntukTarget($paket->value, 0)['kuota_maksimal'],
        ], PaketLangganan::pilihanMandiri());
    }

    public function store(Request $request, OnboardPesantren $onboard, WilayahLookup $wilayah)
    {
        // Pendaftar yang datang dari demo harus benar-benar keluar dari sesi itu
        // sebelum tenant barunya dibuat — kalau tidak, ia mendaftar sambil masih
        // "login" sebagai wali pesantren contoh.
        if (Auth::check() && $this->sedangMencobaDemo()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if (Auth::check()) {
            return $this->redirectAuthenticated();
        }

        abort_if(! PlatformSetting::registrationOpen(), 404);

        $data = $request->validate([
            // --- Langkah 1: Paket ---
            // Kode di query string tidak pernah dipercaya — sama seperti kode wilayah
            // (§4.1), ia divalidasi ulang di sini. Rule::in dibangun dari
            // PaketLangganan::pilihanMandiri() supaya 'maju' ditolak oleh server, bukan
            // sekadar tidak dirender di form.
            'paket' => ['required', Rule::in(array_column(PaketLangganan::pilihanMandiri(), 'value'))],

            // --- Langkah 2: Data Pesantren ---
            'nama_pesantren' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', new ValidTenantSlug, new SlugNotReserved, 'unique:pesantrens,slug'],
            'wilayah_provinsi' => ['required', 'string', 'regex:/^\d{2}$/'],
            'wilayah_kota' => ['required', 'string', 'regex:/^\d{2}\.\d{2}$/'],
            'wilayah_kecamatan' => ['required', 'string', 'regex:/^\d{2}\.\d{2}\.\d{2}$/'],
            // Satu-satunya kode yang benar-benar diadu ke database. Tiga kode di atasnya
            // hanya dicocokkan dengan hasil turunannya — lihat WilayahJalurValid.
            'wilayah_desa' => ['required', 'string', 'regex:/^\d{2}\.\d{2}\.\d{2}\.\d{4}$/', new WilayahJalurValid($wilayah)],
            // Alamat jalan — melengkapi empat kolom wilayah, bukan menggantikannya.
            // maxLength 500 disamakan dengan kolom Alamat di PesantrenSettingsPage.
            'alamat_pesantren' => ['required', 'string', 'max:500'],
            'telepon_pesantren' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{8,20}$/'],
            'email_pesantren' => ['nullable', 'email', 'max:100'],

            // --- Langkah 3: Penanggung Jawab ---
            'admin_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'admin_whatsapp' => ['required', 'string', 'max:20', new NomorWhatsApp],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ], [
            'paket.required' => 'Paket wajib dipilih.',
            'paket.in' => 'Paket tidak dikenali. Silakan pilih ulang.',
            'wilayah_provinsi.required' => 'Provinsi wajib dipilih.',
            'wilayah_kota.required' => 'Kota/Kabupaten wajib dipilih.',
            'wilayah_kecamatan.required' => 'Kecamatan wajib dipilih.',
            'wilayah_desa.required' => 'Desa/Kelurahan wajib dipilih.',
            'alamat_pesantren.required' => 'Alamat pesantren wajib diisi.',
            'wilayah_provinsi.regex' => 'Provinsi tidak dikenali. Silakan pilih ulang.',
            'wilayah_kota.regex' => 'Kota/Kabupaten tidak dikenali. Silakan pilih ulang.',
            'wilayah_kecamatan.regex' => 'Kecamatan tidak dikenali. Silakan pilih ulang.',
            'wilayah_desa.regex' => 'Desa/Kelurahan tidak dikenali. Silakan pilih ulang.',
            'telepon_pesantren.regex' => 'Format nomor telepon pesantren tidak valid — gunakan angka saja.',
        ]);

        try {
            $result = $onboard->execute(
                namaPesantren: $data['nama_pesantren'],
                slug: $data['slug'],
                adminName: $data['admin_name'],
                adminEmail: $data['email'],
                adminPassword: $data['password'],
                // Disimpan ternormalisasi (62…), sama seperti jalur impor Excel. Sampai
                // v4.51 kolom ini selalu kosong untuk pendaftar mandiri — dan itulah yang
                // membuat WarnExpiringTenantsWhatsApp, CheckExpiredTenants, serta
                // UpgradeOrderService diam-diam return lebih awal bagi mereka.
                adminPhone: app(FonnteWhatsAppService::class)->normalizePhoneNumber($data['admin_whatsapp']),
                profil: $this->rakitProfil($data, $wilayah),
                paket: PaketLangganan::from($data['paket']),
            );
        } catch (QueryException $e) {
            Log::warning('register_onboard_failed', [
                'slug' => $data['slug'],
                'email' => $data['email'],
                'message' => $e->getMessage(),
            ]);

            return back()->withInput()->withErrors([
                'slug' => 'Pendaftaran gagal diproses — kemungkinan subdomain atau email sudah dipakai. Silakan periksa kembali lalu coba lagi.',
            ]);
        }

        $this->kirimEmailSambutan($result);

        // Sengaja TIDAK Auth::login() di sini: sesi yang lahir di apex tidak akan
        // pernah terbaca di host panel (cookie ber-scope host, §1.8). Sesinya
        // dipindahkan lewat tautan sekali pakai — lihat SerahTerimaSesiController.
        return redirect()->away(SerahTerimaSesiController::untuk($result['admin']));
    }

    /**
     * Rakit blob `pesantrens.profil` awal dari kolom langkah 1.
     *
     * Nama wilayah ikut disimpan (didenormalisasi) supaya profil publik, ekspor, dan
     * email tidak pernah butuh join; kodenya disimpan supaya nilainya tetap
     * machine-usable — prefill Select di Pengaturan, dan agregasi sebaran pesantren.
     *
     * `alamat` diisi dari kolomnya sendiri, TIDAK pernah dirangkai dari wilayah.
     * Merangkainya berarti mengarang data yang akan ditimpa begitu admin mengisi alamat
     * sungguhan — dan `alamat` adalah penanda "profil sudah diisi manusia" bagi
     * checklist onboarding (§14), jadi nilainya harus benar-benar berasal dari manusia.
     *
     * Langkah onboarding Profil tetap belum selesai setelah pendaftaran: ia menuntut
     * `alamat` DAN `logo`, dan logo memang belum bisa diunggah dari /register.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function rakitProfil(array $data, WilayahLookup $wilayah): array
    {
        // Sudah dimemo WilayahJalurValid pada request yang sama — tidak ada query kedua.
        $profil = [
            'wilayah' => $wilayah->jalurDariDesa($data['wilayah_desa']),
            'alamat' => $data['alamat_pesantren'],
        ];

        if (filled($data['telepon_pesantren'] ?? null)) {
            $profil['telepon'] = $data['telepon_pesantren'];
        }

        // Key `email_kontak`, bukan `email_pesantren`: itulah key yang sudah dirender
        // resources/views/public/profile.blade.php sejak lama namun tidak pernah ditulis
        // siapa pun.
        if (filled($data['email_pesantren'] ?? null)) {
            $profil['email_kontak'] = $data['email_pesantren'];
        }

        return $profil;
    }

    private function redirectAuthenticated()
    {
        if (Auth::user()->role === 'wali_santri') {
            // Portal wali hidup di host pesantren (§1.8 Fase 1) dan konteks ini berjalan
            // di host platform — route('wali.dashboard') di sini akan gagal karena tidak
            // punya default slug. Bangun URL-nya dari tenant-nya sendiri.
            return redirect()->away(Auth::user()->urlPortalWali());
        }

        return redirect($this->adminUrl());
    }

    private function adminUrl(): string
    {
        return request()->getScheme().'://'.config('app.domain').'/admin';
    }

    /**
     * Dikirim di sini, bukan di dalam OnboardPesantren.
     *
     * Seluruh isi service itu dibungkus DB::transaction, dan email yang terlanjur
     * keluar tidak bisa ikut di-rollback — pesantren akan menerima ucapan selamat
     * datang untuk akun yang batal dibuat.
     *
     * @param  array{pesantren: Pesantren, admin: User}  $result
     */
    private function kirimEmailSambutan(array $result): void
    {
        if (! EmailSetting::get('email_sambutan_enabled')) {
            return;
        }

        if (blank($result['admin']->email)) {
            return;
        }

        Mail::to($result['admin']->email)->queue(
            new SambutanPendaftaran($result['pesantren'], $result['admin'])
        );
    }
}
