<?php

namespace App\Imports;

use App\Enums\JenisKelamin;
use App\Enums\UserRole;
use App\Exceptions\SantriQuotaExceededException;
use App\Models\Kamar;
use App\Models\Kelas;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\User;
use App\Observers\ActivityLogger;
use App\Services\FonnteWhatsAppService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class SantriImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    public int $imported = 0;

    public int $updated = 0;

    public int $skipped = 0;

    public array $errors = [];

    private array $kelasCache = [];

    private array $kamarCache = [];

    /**
     * Hasil resolusi wali per baris, dikunci "email:x" DAN "hp:y" sekaligus
     * supaya satu orang tetap satu akun walau baris-barisnya menyebut penanda
     * yang berbeda. Menyimpan null juga (konflik), agar peringatannya tidak
     * berulang di setiap baris.
     *
     * @var array<string, int|null>
     */
    private array $waliCache = [];

    private ?Pesantren $pesantren = null;

    private bool $pesantrenDimuat = false;

    /**
     * @param  bool  $perbaruiYangAda  Bila true, baris yang NIS-nya sudah terdaftar
     *                                 memperbarui santri itu alih-alih dilewati.
     *                                 Default false supaya perilaku lama (impor =
     *                                 hanya menambah) tidak berubah diam-diam:
     *                                 mengunggah ulang file lama tidak boleh
     *                                 memundurkan data yang sudah disunting manual.
     */
    public function __construct(
        private int $pesantrenId,
        private bool $perbaruiYangAda = false,
    ) {}

    /**
     * Analisa file tanpa menyimpan apa pun — dipakai untuk preview sebelum admin
     * konfirmasi import. Meniru aturan skip yang sama seperti collection() (data
     * wajib kosong, NIS duplikat, NIS bekas santri terhapus, kuota santri aktif),
     * termasuk mode $perbaruiYangAda: angka yang tampil di pratinjau harus
     * berubah begitu togglenya dinyalakan, kalau tidak admin menekan tombol
     * berdasarkan ringkasan yang bukan miliknya.
     *
     * @return array{total: int, akan_diimpor: int, akan_diperbarui: int, duplikat: int, dihapus: int, data_wajib_kosong: int, melebihi_kuota: int, wali_baru: int}
     */
    public function analyze(Collection $rows): array
    {
        $summary = [
            'total' => 0,
            'akan_diimpor' => 0,
            'akan_diperbarui' => 0,
            'duplikat' => 0,
            'dihapus' => 0,
            'data_wajib_kosong' => 0,
            'melebihi_kuota' => 0,
            'wali_baru' => 0,
        ];

        $pesantren = $this->pesantren();
        $sisaKuota = $pesantren ? max(0, $pesantren->max_santri_kuota - $pesantren->jumlahSantriAktif()) : PHP_INT_MAX;
        $akanMenambahAktif = 0;
        $waliSeen = [];
        $terdaftarMap = $this->existingNisMap($rows);
        $seenInBatch = [];

        foreach ($rows as $row) {
            $summary['total']++;

            $nis = trim((string) ($row['nis'] ?? ''));
            $namaLengkap = trim((string) ($row['nama_lengkap'] ?? ''));

            if ($nis === '' || $namaLengkap === '') {
                $summary['data_wajib_kosong']++;

                continue;
            }

            if (isset($seenInBatch[$nis])) {
                $summary['duplikat']++;

                continue;
            }
            $seenInBatch[$nis] = true;

            $terdaftar = $terdaftarMap[$nis] ?? null;

            if ($terdaftar?->trashed()) {
                $summary['dihapus']++;

                continue;
            }

            if ($terdaftar !== null && ! $this->perbaruiYangAda) {
                $summary['duplikat']++;

                continue;
            }

            // Yang dihitung ke kuota adalah baris yang MENAMBAH santri aktif, bukan
            // sekadar baris berstatus aktif: memperbarui santri yang sudah aktif
            // tidak menambah beban kuota apa pun.
            if ($this->akanMenambahSantriAktif($row, $terdaftar)) {
                if ($akanMenambahAktif >= $sisaKuota) {
                    $summary['melebihi_kuota']++;

                    continue;
                }
                $akanMenambahAktif++;
            }

            // Pencarian dua arah yang sama persis dengan resolveWali(), kalau tidak
            // pratinjau menjanjikan akun wali baru untuk orang yang sebenarnya
            // sudah punya akun lewat penanda satunya.
            ['email' => $waliEmail, 'hp' => $waliNoHp] = $this->penandaWali($row);

            if ($waliEmail !== null || $waliNoHp !== null) {
                $kunciWali = array_values(array_filter([
                    $waliEmail !== null ? 'email:'.$waliEmail : null,
                    $waliNoHp !== null ? 'hp:'.$waliNoHp : null,
                ]));

                $sudahDihitung = false;
                foreach ($kunciWali as $satuKunci) {
                    $sudahDihitung = $sudahDihitung || array_key_exists($satuKunci, $waliSeen);
                }

                if (! $sudahDihitung) {
                    foreach ($kunciWali as $satuKunci) {
                        $waliSeen[$satuKunci] = true;
                    }

                    if ($this->cariWali($waliEmail, $waliNoHp) === null) {
                        $summary['wali_baru']++;
                    }
                }
            }

            if ($terdaftar !== null) {
                $summary['akan_diperbarui']++;
            } else {
                $summary['akan_diimpor']++;
            }
        }

        return $summary;
    }

    /**
     * Apakah baris ini menghasilkan SATU santri aktif baru yang sebelumnya belum
     * terhitung di kuota?
     *
     * Untuk santri baru: ya, kecuali statusnya non-aktif. Untuk pembaruan: hanya
     * bila kolom `status` benar-benar diisi (kolom kosong berarti "jangan ubah")
     * DAN santrinya saat ini non-aktif. Tanpa pemisahan ini, mengaktifkan kembali
     * santri lewat impor jadi celah melewati kuota — SantriObserver hanya memeriksa
     * kuota di event `creating`, tidak di `updating`.
     */
    private function akanMenambahSantriAktif(array|Collection $row, ?Santri $terdaftar): bool
    {
        $statusAktifDiminta = ! $this->isStatusNonAktif($row['status'] ?? null);

        if ($terdaftar === null) {
            return $statusAktifDiminta;
        }

        if (trim((string) ($row['status'] ?? '')) === '') {
            return false;
        }

        return $statusAktifDiminta && ! $terdaftar->status_aktif;
    }

    public function collection(Collection $rows): void
    {
        $terdaftarMap = $this->existingNisMap($rows);
        $seenInBatch = [];

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;

            $nis = trim((string) ($row['nis'] ?? ''));
            $namaLengkap = trim((string) ($row['nama_lengkap'] ?? ''));

            if ($nis === '' || $namaLengkap === '') {
                $this->errors[] = "Baris {$rowNum}: NIS dan Nama Lengkap wajib diisi.";
                $this->skipped++;

                continue;
            }

            // NIS ganda DI DALAM file selalu dilewati, juga saat mode perbarui
            // menyala: dua baris untuk satu santri berarti baris mana yang menang
            // ditentukan urutan file, dan itu bukan hasil yang bisa diprediksi admin.
            if (isset($seenInBatch[$nis])) {
                $this->errors[] = "Baris {$rowNum}: NIS '{$nis}' muncul lebih dari sekali di file ini, baris ini dilewati.";
                $this->skipped++;

                continue;
            }
            $seenInBatch[$nis] = true;

            $terdaftar = $terdaftarMap[$nis] ?? null;

            // Santri terhapus tetap memblokir NIS-nya, bahkan dalam mode perbarui:
            // penghapusan harus tetap berarti penghapusan, dan impor tidak boleh
            // diam-diam menghidupkan kembali santri yang sengaja dibuang.
            if ($terdaftar?->trashed()) {
                $this->errors[] = "Baris {$rowNum}: NIS '{$nis}' milik santri yang sudah dihapus, dilewati. Pulihkan dulu santrinya bila memang ingin diperbarui.";
                $this->skipped++;

                continue;
            }

            if ($terdaftar !== null && ! $this->perbaruiYangAda) {
                $this->errors[] = "Baris {$rowNum}: NIS '{$nis}' sudah terdaftar, dilewati. Centang \"Perbarui data santri yang sudah terdaftar\" bila ingin memperbaruinya.";
                $this->skipped++;

                continue;
            }

            // Kuota diperiksa SEBELUM resolveKolomTerisi(), bukan sesudah: method itu
            // memanggil resolveWali() yang bisa membuat akun wali baru, dan baris yang
            // pada akhirnya dilewati tidak boleh meninggalkan akun wali yatim.
            //
            // SantriObserver memeriksa kuota di `creating` saja, tidak di `updating`.
            // Tanpa pemeriksaan di sini, mengaktifkan kembali santri lewat impor jadi
            // jalan memutar untuk melewati batas paket.
            if ($terdaftar !== null
                && $this->akanMenambahSantriAktif($row, $terdaftar)
                && $this->pesantren()?->isQuotaFull()) {
                $this->errors[] = "Baris {$rowNum}: Kuota santri aktif penuh, NIS '{$nis}' tidak diaktifkan kembali dan barisnya dilewati.";
                $this->skipped++;

                continue;
            }

            $kolom = $this->resolveKolomTerisi($row, $rowNum);

            if ($terdaftar !== null) {
                $this->perbarui($terdaftar, $namaLengkap, $kolom, $rowNum);

                continue;
            }

            try {
                Santri::create([
                    'pesantren_id' => $this->pesantrenId,
                    'nis' => $nis,
                    'nama_lengkap' => $namaLengkap,
                    // status_aktif didahulukan sebagai default, lalu ditimpa $kolom
                    // bila kolom `status` memang diisi — menyamai resolveStatusAktif()
                    // yang menganggap status kosong sebagai Aktif.
                    'status_aktif' => true,
                    ...$kolom,
                ]);

                $this->imported++;
            } catch (SantriQuotaExceededException $e) {
                $this->errors[] = "Baris {$rowNum}: {$e->getMessage()}";
                $this->skipped++;
            } catch (\Throwable $e) {
                $this->errors[] = "Baris {$rowNum}: Gagal menyimpan data ({$e->getMessage()}).";
                $this->skipped++;
            }
        }
    }

    /**
     * Terapkan pembaruan ke santri yang sudah ada. Hanya kolom yang benar-benar
     * terisi di file yang menimpa — sel kosong berarti "jangan ubah", bukan
     * "kosongkan". Itu yang membuat file parsial (mis. hanya nis + kelas untuk
     * kenaikan kelas massal) aman dipakai tanpa menghapus biodata.
     */
    private function perbarui(Santri $santri, string $namaLengkap, array $kolom, int $rowNum): void
    {
        $santri->fill(['nama_lengkap' => $namaLengkap, ...$kolom]);

        if (! $santri->isDirty()) {
            // Tetap dihitung sebagai berhasil: dari sudut pandang admin barisnya
            // memang sudah sesuai file, bukan gagal diproses.
            $this->updated++;

            return;
        }

        try {
            $sesudah = $santri->getDirty();
            $sebelum = array_intersect_key($santri->getOriginal(), $sesudah);
            $santri->save();

            // Pembaruan massal tanpa jejak audit adalah lubang yang serius: satu
            // file salah bisa menimpa ratusan baris tanpa cara menelusurinya.
            // SantriObserver tidak punya hook `updated`, jadi dicatat dari sini —
            // suntingan manual di panel sengaja tidak ikut terdampak.
            ActivityLogger::log('santri.updated_via_import', $santri, $sebelum, $sesudah);

            $this->updated++;
        } catch (\Throwable $e) {
            $this->errors[] = "Baris {$rowNum}: Gagal memperbarui data ({$e->getMessage()}).";
            $this->skipped++;
        }
    }

    /**
     * Kolom yang BENAR-BENAR terisi (dan bisa dipetakan) dari satu baris file.
     *
     * Dipakai bersama oleh jalur tambah dan jalur perbarui, jadi pemetaan kolom
     * hanya ditulis sekali. Kolom yang tidak muncul di array hasil berarti selnya
     * kosong atau nilainya tidak dikenali — untuk santri baru ia jatuh ke default
     * kolom (semuanya nullable), untuk pembaruan ia dibiarkan apa adanya.
     *
     * Peringatan untuk sel yang diisi tapi tidak dikenali (tanggal salah format,
     * kelas tidak ada) tetap dicatat oleh masing-masing resolver.
     *
     * @return array<string, mixed>
     */
    private function resolveKolomTerisi(array|Collection $row, int $rowNum): array
    {
        $kolom = [];

        foreach ([
            'nama_panggilan' => 'nama_panggilan',
            'nama_ayah' => 'nama_ayah',
            'nama_ibu' => 'nama_ibu',
            'alamat_lengkap' => 'alamat_lengkap',
            'cita_cita' => 'cita_cita',
        ] as $kolomDb => $kolomFile) {
            $nilai = $this->nullable($row[$kolomFile] ?? null);

            if ($nilai !== null) {
                $kolom[$kolomDb] = $nilai;
            }
        }

        if (is_numeric($row['jumlah_saudara'] ?? null)) {
            $kolom['jumlah_saudara'] = (int) $row['jumlah_saudara'];
        }

        if ($this->nullable($row['tanggal_lahir'] ?? null) !== null
            && ($tanggalLahir = $this->parseTanggal($row['tanggal_lahir'] ?? null, $rowNum)) !== null) {
            $kolom['tanggal_lahir'] = $tanggalLahir;
        }

        if ($this->nullable($row['jenis_kelamin'] ?? null) !== null
            && ($jenisKelamin = $this->resolveJenisKelamin($row['jenis_kelamin'] ?? null, $rowNum)) !== null) {
            $kolom['jenis_kelamin'] = $jenisKelamin;
        }

        if ($this->nullable($row['kelas'] ?? null) !== null
            && ($kelasId = $this->resolveKelas($row['kelas'] ?? null, $rowNum)) !== null) {
            $kolom['kelas_id'] = $kelasId;
        }

        if ($this->nullable($row['kamar'] ?? null) !== null
            && ($kamarId = $this->resolveKamar($row['kamar'] ?? null, $rowNum)) !== null) {
            $kolom['kamar_id'] = $kamarId;
        }

        if ($this->nullable($row['status'] ?? null) !== null) {
            $kolom['status_aktif'] = $this->resolveStatusAktif($row['status'] ?? null, $rowNum);
        }

        // resolveWali() mengembalikan null baik saat kolom wali kosong maupun saat
        // konflik (email milik pesantren lain, peran bukan wali). Keduanya berarti
        // hal yang sama di sini: jangan sentuh tautan wali yang sudah ada.
        if (($waliSantriId = $this->resolveWali($row, $rowNum)) !== null) {
            $kolom['wali_santri_id'] = $waliSantriId;
        }

        return $kolom;
    }

    /**
     * Pesantren pemilik impor ini, dimuat sekali saja.
     *
     * Dihafal karena pemeriksaan kuota berjalan per baris; yang tidak dihafal
     * adalah hitungan santri aktifnya — `isQuotaFull()` menghitung ulang setiap
     * kali dipanggil supaya baris yang baru saja dibuat di impor yang sama ikut
     * terhitung.
     */
    private function pesantren(): ?Pesantren
    {
        if (! $this->pesantrenDimuat) {
            $this->pesantren = Pesantren::find($this->pesantrenId);
            $this->pesantrenDimuat = true;
        }

        return $this->pesantren;
    }

    /**
     * Santri yang NIS-nya muncul di file, dipetakan `nis => model`, termasuk yang
     * soft-deleted supaya NIS bekas tetap terdeteksi. Satu query untuk seluruh
     * file, bukan satu query per baris.
     *
     * Modelnya disimpan utuh, bukan sekadar penanda ada/tidak, karena jalur
     * perbarui membutuhkan objeknya langsung untuk di-fill dan `status_aktif`
     * saat ini untuk memutuskan apakah baris ini menambah beban kuota.
     *
     * @return array<string, Santri>
     */
    private function existingNisMap(Collection $rows): array
    {
        $nisList = $rows
            ->map(fn ($row) => trim((string) ($row['nis'] ?? '')))
            ->filter(fn ($nis) => $nis !== '')
            ->unique()
            ->values()
            ->all();

        if (empty($nisList)) {
            return [];
        }

        return Santri::withTrashed()
            ->where('pesantren_id', $this->pesantrenId)
            ->whereIn('nis', $nisList)
            ->get()
            ->keyBy('nis')
            ->all();
    }

    private function parseTanggal(mixed $value, int $rowNum): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                $date = Date::excelToDateTimeObject((float) $value);

                return Carbon::instance($date)->format('Y-m-d');
            }

            return Carbon::createFromFormat('d/m/Y', trim((string) $value))->format('Y-m-d');
        } catch (\Exception) {
            $this->errors[] = "Baris {$rowNum}: Format tanggal lahir '{$value}' tidak valid, kolom diabaikan.";

            return null;
        }
    }

    private function resolveJenisKelamin(mixed $value, int $rowNum): ?string
    {
        $raw = trim((string) ($value ?? ''));

        if ($raw === '') {
            return null;
        }

        $normalized = strtolower(str_replace([' ', '-'], '', $raw));

        if (in_array($normalized, ['l', 'laki', 'lakilaki', 'pria'], true)) {
            return JenisKelamin::LakiLaki->value;
        }

        if (in_array($normalized, ['p', 'perempuan', 'wanita'], true)) {
            return JenisKelamin::Perempuan->value;
        }

        $this->errors[] = "Baris {$rowNum}: Jenis kelamin '{$raw}' tidak dikenali, kolom diabaikan.";

        return null;
    }

    private function resolveStatusAktif(mixed $value, int $rowNum): bool
    {
        $raw = trim((string) ($value ?? ''));

        if ($raw === '') {
            return true;
        }

        $normalized = strtolower(str_replace([' ', '-', '_'], '', $raw));

        if (in_array($normalized, ['aktif', 'active', 'ya', 'yes', '1'], true)) {
            return true;
        }

        if ($this->isStatusNonAktif($raw)) {
            return false;
        }

        $this->errors[] = "Baris {$rowNum}: Status '{$raw}' tidak dikenali, dianggap Aktif.";

        return true;
    }

    private function isStatusNonAktif(mixed $value): bool
    {
        $normalized = strtolower(str_replace([' ', '-', '_'], '', trim((string) ($value ?? ''))));

        return in_array($normalized, ['nonaktif', 'tidakaktif', 'inactive', 'tidak', 'no', '0'], true);
    }

    private function resolveKelas(mixed $value, int $rowNum): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $nama = trim((string) $value);
        $cacheKey = mb_strtolower($nama);

        if (! array_key_exists($cacheKey, $this->kelasCache)) {
            $this->kelasCache[$cacheKey] = Kelas::where('pesantren_id', $this->pesantrenId)
                ->whereRaw('LOWER(nama_kelas) = ?', [$cacheKey])
                ->value('id');
        }

        if (! $this->kelasCache[$cacheKey]) {
            $this->errors[] = "Baris {$rowNum}: Kelas '{$nama}' tidak ditemukan, kolom diabaikan.";
        }

        return $this->kelasCache[$cacheKey];
    }

    private function resolveKamar(mixed $value, int $rowNum): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $nama = trim((string) $value);
        $cacheKey = mb_strtolower($nama);

        if (! array_key_exists($cacheKey, $this->kamarCache)) {
            $this->kamarCache[$cacheKey] = Kamar::where('pesantren_id', $this->pesantrenId)
                ->whereRaw('LOWER(nama_kamar) = ?', [$cacheKey])
                ->value('id');
        }

        if (! $this->kamarCache[$cacheKey]) {
            $this->errors[] = "Baris {$rowNum}: Kamar '{$nama}' tidak ditemukan, kolom diabaikan.";
        }

        return $this->kamarCache[$cacheKey];
    }

    private function isValidEmailFormat(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Dua penanda identitas wali dari satu baris, sudah dibersihkan: email
     * di-lowercase (hanya bila formatnya sah) dan nomor HP dinormalkan ke bentuk
     * 62xxx (hanya bila bisa diurai).
     *
     * Dipakai bersama oleh resolveWali() dan analyze() supaya pratinjau dan
     * impor sungguhan menilai baris yang sama dengan cara yang sama.
     *
     * @return array{email: ?string, hp: ?string}
     */
    private function penandaWali(array|Collection $row): array
    {
        $emailRaw = $this->nullable($row['wali_email'] ?? null);
        $noHpRaw = $this->nullable($row['wali_no_hp'] ?? null);

        return [
            'email' => ($emailRaw !== null && $this->isValidEmailFormat($emailRaw))
                ? mb_strtolower($emailRaw)
                : null,
            'hp' => $noHpRaw !== null ? $this->normalizePhone($noHpRaw) : null,
        ];
    }

    /**
     * Cari akun wali yang sudah ada lewat KEDUA penanda, bukan salah satu saja.
     *
     * Sebelumnya pencarian hanya mengikuti kolom yang kebetulan diisi: baris
     * ber-email hanya dicari lewat email, baris ber-nomor hanya lewat nomor.
     * Akibatnya satu orang tua bisa punya dua akun — impor pertama menautkannya
     * lewat nomor (akun lahir dengan email NULL), impor kedua membawa emailnya,
     * pencarian email tidak menemukan apa pun, dan akun kedua lahir. Santrinya
     * ikut berpindah ke akun baru itu, sehingga magic link portal wali yang
     * sudah telanjur dibagikan menunjuk akun yatim.
     *
     * Email dicari GLOBAL (unik lintas tenant di tabel users), nomor HP di-scope
     * ke pesantren karena ia tidak unik secara global.
     *
     * @return array{user: User, lewat: 'email'|'hp'}|null
     */
    private function cariWali(?string $emailLower, ?string $normalizedHp): ?array
    {
        if ($emailLower !== null
            && ($user = User::whereRaw('LOWER(email) = ?', [$emailLower])->first()) !== null) {
            return ['user' => $user, 'lewat' => 'email'];
        }

        if ($normalizedHp !== null
            && ($user = User::where('pesantren_id', $this->pesantrenId)
                ->where('phone_number', $normalizedHp)
                ->first()) !== null) {
            return ['user' => $user, 'lewat' => 'hp'];
        }

        return null;
    }

    private function resolveWali(array|Collection $row, int $rowNum): ?int
    {
        $nama = $this->nullable($row['wali_nama'] ?? null);
        $noHpRaw = $this->nullable($row['wali_no_hp'] ?? null);
        $emailRaw = $this->nullable($row['wali_email'] ?? null);

        if ($nama === null && $emailRaw === null && $noHpRaw === null) {
            return null;
        }

        if ($emailRaw === null && $noHpRaw === null) {
            $this->errors[] = "Baris {$rowNum}: Data wali diisi tapi wali_email dan wali_no_hp kosong, wali tidak ditautkan (santri tetap dibuat).";

            return null;
        }

        // Email yang formatnya salah membatalkan baris ini, tidak jatuh ke nomor HP:
        // alamat yang keliru hampir selalu berarti salah ketik, dan menautkan diam-diam
        // ke orang lain lewat nomor akan menyembunyikan kesalahan itu.
        if ($emailRaw !== null && ! $this->isValidEmailFormat($emailRaw)) {
            $this->errors[] = "Baris {$rowNum}: Format wali_email '{$emailRaw}' tidak valid, wali tidak ditautkan (santri tetap dibuat).";

            return null;
        }

        ['email' => $emailLower, 'hp' => $normalizedHp] = $this->penandaWali($row);

        if ($noHpRaw !== null && $normalizedHp === null) {
            if ($emailLower === null) {
                $this->errors[] = "Baris {$rowNum}: Format wali_no_hp '{$noHpRaw}' tidak valid, wali tidak ditautkan (santri tetap dibuat).";

                return null;
            }

            $this->errors[] = "Baris {$rowNum}: Format wali_no_hp '{$noHpRaw}' tidak valid, nomornya diabaikan (wali tetap ditautkan lewat email).";
        }

        // Satu cache untuk kedua penanda, dan hasilnya didaftarkan di bawah
        // keduanya sekaligus. Dengan begitu baris kakak-adik yang menyebut orang
        // yang sama lewat penanda berbeda tetap mendarat di akun yang sama tanpa
        // query ulang.
        $kunci = array_values(array_filter([
            $emailLower !== null ? 'email:'.$emailLower : null,
            $normalizedHp !== null ? 'hp:'.$normalizedHp : null,
        ]));

        foreach ($kunci as $satuKunci) {
            if (array_key_exists($satuKunci, $this->waliCache)) {
                return $this->ingatWali($kunci, $this->waliCache[$satuKunci]);
            }
        }

        $temuan = $this->cariWali($emailLower, $normalizedHp);

        if ($temuan !== null) {
            $wali = $temuan['user'];

            // Hanya mungkin dari pencarian email — pencarian nomor sudah di-scope
            // ke pesantren ini.
            if ((int) $wali->pesantren_id !== $this->pesantrenId) {
                $this->errors[] = "Baris {$rowNum}: Email wali '{$emailRaw}' sudah terdaftar di pesantren lain, wali tidak ditautkan (santri tetap dibuat).";

                return $this->ingatWali($kunci, null);
            }

            if ($wali->role !== UserRole::WaliSantri->value) {
                $this->errors[] = $temuan['lewat'] === 'email'
                    ? "Baris {$rowNum}: Email wali '{$emailRaw}' sudah terdaftar dengan peran lain (bukan Wali Santri), wali tidak ditautkan (santri tetap dibuat)."
                    : "Baris {$rowNum}: No HP wali '{$noHpRaw}' sudah terdaftar dengan peran lain (bukan Wali Santri), wali tidak ditautkan (santri tetap dibuat).";

                return $this->ingatWali($kunci, null);
            }

            $this->lengkapiWali($wali, $emailRaw, $normalizedHp, $rowNum);

            return $this->ingatWali($kunci, $wali->id);
        }

        try {
            $wali = User::create([
                'pesantren_id' => $this->pesantrenId,
                'name' => $nama ?? $emailRaw ?? $noHpRaw,
                'email' => $emailLower !== null ? $emailRaw : null,
                // Selalu bentuk ternormalisasi, apa pun jalur pembuatannya. Dulu
                // jalur email menyimpan nomor mentah sementara jalur nomor
                // menyimpan bentuk 62xxx, sehingga dua akun untuk orang yang sama
                // tidak akan pernah saling ditemukan lagi lewat nomor.
                'phone_number' => $normalizedHp,
                'password' => Str::password(12),
                'role' => UserRole::WaliSantri->value,
            ]);

            return $this->ingatWali($kunci, $wali->id);
        } catch (\Throwable $e) {
            $penanda = $emailRaw ?? $noHpRaw;
            $this->errors[] = "Baris {$rowNum}: Gagal membuat akun wali baru untuk '{$penanda}' ({$e->getMessage()}).";

            return $this->ingatWali($kunci, null);
        }
    }

    /**
     * Isi penanda wali yang masih KOSONG di akun yang sudah ada — dan hanya yang
     * kosong.
     *
     * Inilah yang benar-benar menutup celah akun ganda: tanpa ini, akun yang lahir
     * dari impor bernomor-saja selamanya ber-email NULL, sehingga impor berikutnya
     * yang hanya membawa email tidak akan pernah menemukannya.
     *
     * Nilai yang sudah terisi tidak pernah ditimpa. Mengoreksi email atau nomor
     * wali yang salah tetap harus lewat menu Pengguna — impor adalah berkas yang
     * dirakit massal, terlalu mudah keliru untuk dipercaya menimpa kontak orang.
     */
    private function lengkapiWali(User $wali, ?string $emailRaw, ?string $normalizedHp, int $rowNum): void
    {
        $isi = [];

        if ($emailRaw !== null && blank($wali->email)) {
            $isi['email'] = $emailRaw;
        }

        if ($normalizedHp !== null && blank($wali->phone_number)) {
            $isi['phone_number'] = $normalizedHp;
        }

        if ($isi === []) {
            return;
        }

        try {
            $wali->forceFill($isi)->save();
        } catch (\Throwable $e) {
            // Tautan santri ke wali ini sudah benar dan tetap dipertahankan; yang
            // gagal hanya pelengkapan kontaknya, jadi barisnya tidak dibatalkan.
            $this->errors[] = "Baris {$rowNum}: Kontak wali '{$wali->name}' gagal dilengkapi ({$e->getMessage()}), tautan santrinya tetap dibuat.";
        }
    }

    /**
     * Simpan hasil resolusi di bawah SEMUA penanda baris ini sekaligus, lalu
     * kembalikan. Termasuk hasil null (konflik) supaya baris berikutnya dengan
     * penanda sama tidak mengulang query maupun pesan peringatannya.
     *
     * @param  list<string>  $kunci
     */
    private function ingatWali(array $kunci, ?int $waliId): ?int
    {
        foreach ($kunci as $satuKunci) {
            $this->waliCache[$satuKunci] = $waliId;
        }

        return $waliId;
    }

    private function normalizePhone(string $phone): ?string
    {
        return (new FonnteWhatsAppService)->normalizePhoneNumber($phone);
    }

    private function nullable(mixed $value): ?string
    {
        $str = trim((string) ($value ?? ''));

        return $str !== '' ? $str : null;
    }
}
