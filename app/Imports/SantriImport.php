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

    public int $skipped = 0;

    public array $errors = [];

    private array $kelasCache = [];

    private array $kamarCache = [];

    private array $waliCache = [];

    private array $waliPhoneCache = [];

    public function __construct(
        private int $pesantrenId
    ) {}

    /**
     * Analisa file tanpa menyimpan apa pun — dipakai untuk preview sebelum admin
     * konfirmasi import. Meniru aturan skip yang sama seperti collection() (data
     * wajib kosong, NIS duplikat termasuk soft-deleted, kuota santri aktif).
     *
     * @return array{total: int, akan_diimpor: int, duplikat: int, data_wajib_kosong: int, melebihi_kuota: int, wali_baru: int}
     */
    public function analyze(Collection $rows): array
    {
        $summary = [
            'total' => 0,
            'akan_diimpor' => 0,
            'duplikat' => 0,
            'data_wajib_kosong' => 0,
            'melebihi_kuota' => 0,
            'wali_baru' => 0,
        ];

        $pesantren = Pesantren::find($this->pesantrenId);
        $sisaKuota = $pesantren ? max(0, $pesantren->max_santri_kuota - $pesantren->jumlahSantriAktif()) : PHP_INT_MAX;
        $akanMenambahAktif = 0;
        $waliSeen = [];
        $existingNis = $this->existingNisSet($rows);
        $seenInBatch = [];

        foreach ($rows as $row) {
            $summary['total']++;

            $nis = trim((string) ($row['nis'] ?? ''));
            $namaLengkap = trim((string) ($row['nama_lengkap'] ?? ''));

            if ($nis === '' || $namaLengkap === '') {
                $summary['data_wajib_kosong']++;

                continue;
            }

            if (isset($existingNis[$nis]) || isset($seenInBatch[$nis])) {
                $summary['duplikat']++;

                continue;
            }
            $seenInBatch[$nis] = true;

            if (! $this->isStatusNonAktif($row['status'] ?? null)) {
                if ($akanMenambahAktif >= $sisaKuota) {
                    $summary['melebihi_kuota']++;

                    continue;
                }
                $akanMenambahAktif++;
            }

            $waliEmail = $this->extractValidWaliEmail($row);
            $waliNoHp = $this->nullable($row['wali_no_hp'] ?? null);

            if ($waliEmail !== null) {
                if (! array_key_exists($waliEmail, $waliSeen)) {
                    $waliSeen[$waliEmail] = true;
                    if ($this->classifyWaliEmail($waliEmail)['status'] === 'not_found') {
                        $summary['wali_baru']++;
                    }
                }
            } elseif ($waliNoHp !== null) {
                $normalized = $this->normalizePhone($waliNoHp);
                if ($normalized !== null && ! array_key_exists($normalized, $waliSeen)) {
                    $waliSeen[$normalized] = true;
                    if ($this->classifyWaliPhone($normalized)['status'] === 'not_found') {
                        $summary['wali_baru']++;
                    }
                }
            }

            $summary['akan_diimpor']++;
        }

        return $summary;
    }

    public function collection(Collection $rows): void
    {
        $existingNis = $this->existingNisSet($rows);
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

            if (isset($existingNis[$nis]) || isset($seenInBatch[$nis])) {
                $this->errors[] = "Baris {$rowNum}: NIS '{$nis}' sudah pernah terdaftar (termasuk data yang dihapus), dilewati.";
                $this->skipped++;

                continue;
            }
            $seenInBatch[$nis] = true;

            $tanggalLahir = $this->parseTanggal($row['tanggal_lahir'] ?? null, $rowNum);
            $jenisKelamin = $this->resolveJenisKelamin($row['jenis_kelamin'] ?? null, $rowNum);
            $kelasId = $this->resolveKelas($row['kelas'] ?? null, $rowNum);
            $kamarId = $this->resolveKamar($row['kamar'] ?? null, $rowNum);
            $statusAktif = $this->resolveStatusAktif($row['status'] ?? null, $rowNum);
            $waliSantriId = $this->resolveWali($row, $rowNum);

            try {
                Santri::create([
                    'pesantren_id' => $this->pesantrenId,
                    'nis' => $nis,
                    'nama_lengkap' => $namaLengkap,
                    'nama_panggilan' => $this->nullable($row['nama_panggilan'] ?? null),
                    'tanggal_lahir' => $tanggalLahir,
                    'jenis_kelamin' => $jenisKelamin,
                    'nama_ayah' => $this->nullable($row['nama_ayah'] ?? null),
                    'nama_ibu' => $this->nullable($row['nama_ibu'] ?? null),
                    'alamat_lengkap' => $this->nullable($row['alamat_lengkap'] ?? null),
                    'jumlah_saudara' => is_numeric($row['jumlah_saudara'] ?? null) ? (int) $row['jumlah_saudara'] : null,
                    'cita_cita' => $this->nullable($row['cita_cita'] ?? null),
                    'kelas_id' => $kelasId,
                    'kamar_id' => $kamarId,
                    'status_aktif' => $statusAktif,
                    'wali_santri_id' => $waliSantriId,
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
     * Snapshot NIS yang sudah terdaftar (termasuk soft-deleted) untuk seluruh NIS
     * yang muncul di file — satu query, bukan satu query per baris.
     *
     * @return array<string, true>
     */
    private function existingNisSet(Collection $rows): array
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
            ->pluck('nis')
            ->flip()
            ->map(fn () => true)
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

    private function extractValidWaliEmail(array $row): ?string
    {
        $email = $this->nullable($row['wali_email'] ?? null);

        if ($email === null || ! $this->isValidEmailFormat($email)) {
            return null;
        }

        return mb_strtolower($email);
    }

    /**
     * Klasifikasi email wali secara read-only — dipakai bersama oleh resolveWali()
     * (commit sungguhan) dan analyze() (preview). Lookup GLOBAL tanpa scope
     * pesantren_id karena email unik secara global di tabel users.
     *
     * @return array{status: 'not_found'|'reusable'|'conflict_pesantren'|'conflict_role', user_id?: int}
     */
    private function classifyWaliEmail(string $emailLower): array
    {
        $user = User::whereRaw('LOWER(email) = ?', [$emailLower])->first();

        if (! $user) {
            return ['status' => 'not_found'];
        }

        if ((int) $user->pesantren_id !== $this->pesantrenId) {
            return ['status' => 'conflict_pesantren'];
        }

        if ($user->role !== UserRole::WaliSantri->value) {
            return ['status' => 'conflict_role'];
        }

        return ['status' => 'reusable', 'user_id' => $user->id];
    }

    private function resolveWali(array $row, int $rowNum): ?int
    {
        $nama = $this->nullable($row['wali_nama'] ?? null);
        $noHp = $this->nullable($row['wali_no_hp'] ?? null);
        $emailRaw = $this->nullable($row['wali_email'] ?? null);

        if ($nama === null && $emailRaw === null && $noHp === null) {
            return null;
        }

        if ($emailRaw === null) {
            if ($noHp !== null) {
                return $this->resolveWaliByPhone($noHp, $nama, $rowNum);
            }

            $this->errors[] = "Baris {$rowNum}: Data wali diisi tapi wali_email dan wali_no_hp kosong, wali tidak ditautkan (santri tetap dibuat).";

            return null;
        }

        if (! $this->isValidEmailFormat($emailRaw)) {
            $this->errors[] = "Baris {$rowNum}: Format wali_email '{$emailRaw}' tidak valid, wali tidak ditautkan (santri tetap dibuat).";

            return null;
        }

        $cacheKey = mb_strtolower($emailRaw);

        if (array_key_exists($cacheKey, $this->waliCache)) {
            return $this->waliCache[$cacheKey];
        }

        $classification = $this->classifyWaliEmail($cacheKey);

        if ($classification['status'] === 'reusable') {
            return $this->waliCache[$cacheKey] = $classification['user_id'];
        }

        if ($classification['status'] === 'conflict_pesantren') {
            $this->errors[] = "Baris {$rowNum}: Email wali '{$emailRaw}' sudah terdaftar di pesantren lain, wali tidak ditautkan (santri tetap dibuat).";

            return $this->waliCache[$cacheKey] = null;
        }

        if ($classification['status'] === 'conflict_role') {
            $this->errors[] = "Baris {$rowNum}: Email wali '{$emailRaw}' sudah terdaftar dengan peran lain (bukan Wali Santri), wali tidak ditautkan (santri tetap dibuat).";

            return $this->waliCache[$cacheKey] = null;
        }

        try {
            $user = User::create([
                'pesantren_id' => $this->pesantrenId,
                'name' => $nama ?? $emailRaw,
                'email' => $emailRaw,
                'phone_number' => $noHp,
                'password' => Str::password(12),
                'role' => UserRole::WaliSantri->value,
            ]);

            return $this->waliCache[$cacheKey] = $user->id;
        } catch (\Throwable $e) {
            $this->errors[] = "Baris {$rowNum}: Gagal membuat akun wali baru untuk '{$emailRaw}' ({$e->getMessage()}).";

            return $this->waliCache[$cacheKey] = null;
        }
    }

    /**
     * Klasifikasi nomor WA wali secara read-only — dipakai bersama oleh
     * resolveWaliByPhone() (commit sungguhan) dan analyze() (preview). Lookup
     * di-scope ke pesantren_id (beda dari email yang global) karena nomor HP
     * tidak unik secara global di tabel users.
     *
     * @return array{status: 'not_found'|'reusable'|'conflict_role', user_id?: int}
     */
    private function classifyWaliPhone(string $normalized): array
    {
        $user = User::where('pesantren_id', $this->pesantrenId)
            ->where('phone_number', $normalized)
            ->first();

        if (! $user) {
            return ['status' => 'not_found'];
        }

        if ($user->role !== UserRole::WaliSantri->value) {
            return ['status' => 'conflict_role'];
        }

        return ['status' => 'reusable', 'user_id' => $user->id];
    }

    /**
     * Fallback saat wali_email kosong tapi wali_no_hp diisi — admin pesantren
     * sering cuma punya nomor WA wali, bukan email. Magic link portal wali
     * (VerifyMagicToken) tidak butuh email sama sekali, cuma butuh
     * santri.wali_santri_id menunjuk ke User yang valid.
     */
    private function resolveWaliByPhone(string $noHpRaw, ?string $nama, int $rowNum): ?int
    {
        $normalized = $this->normalizePhone($noHpRaw);

        if ($normalized === null) {
            $this->errors[] = "Baris {$rowNum}: Format wali_no_hp '{$noHpRaw}' tidak valid, wali tidak ditautkan (santri tetap dibuat).";

            return null;
        }

        if (array_key_exists($normalized, $this->waliPhoneCache)) {
            return $this->waliPhoneCache[$normalized];
        }

        $classification = $this->classifyWaliPhone($normalized);

        if ($classification['status'] === 'reusable') {
            return $this->waliPhoneCache[$normalized] = $classification['user_id'];
        }

        if ($classification['status'] === 'conflict_role') {
            $this->errors[] = "Baris {$rowNum}: No HP wali '{$noHpRaw}' sudah terdaftar dengan peran lain (bukan Wali Santri), wali tidak ditautkan (santri tetap dibuat).";

            return $this->waliPhoneCache[$normalized] = null;
        }

        try {
            $user = User::create([
                'pesantren_id' => $this->pesantrenId,
                'name' => $nama ?? $noHpRaw,
                'email' => null,
                'phone_number' => $normalized,
                'password' => Str::password(12),
                'role' => UserRole::WaliSantri->value,
            ]);

            return $this->waliPhoneCache[$normalized] = $user->id;
        } catch (\Throwable $e) {
            $this->errors[] = "Baris {$rowNum}: Gagal membuat akun wali baru untuk no HP '{$noHpRaw}' ({$e->getMessage()}).";

            return $this->waliPhoneCache[$normalized] = null;
        }
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
