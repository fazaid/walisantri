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
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SantriImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public int $imported = 0;

    public int $skipped = 0;

    public array $errors = [];

    private array $kelasCache = [];

    private array $kamarCache = [];

    private array $waliCache = [];

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
            'total'             => 0,
            'akan_diimpor'      => 0,
            'duplikat'          => 0,
            'data_wajib_kosong' => 0,
            'melebihi_kuota'    => 0,
            'wali_baru'         => 0,
        ];

        $pesantren         = Pesantren::find($this->pesantrenId);
        $sisaKuota         = $pesantren ? max(0, $pesantren->max_santri_kuota - $pesantren->jumlahSantriAktif()) : PHP_INT_MAX;
        $akanMenambahAktif = 0;
        $waliSeen          = [];

        foreach ($rows as $row) {
            $summary['total']++;

            $nis         = trim((string) ($row['nis'] ?? ''));
            $namaLengkap = trim((string) ($row['nama_lengkap'] ?? ''));

            if ($nis === '' || $namaLengkap === '') {
                $summary['data_wajib_kosong']++;
                continue;
            }

            if (Santri::withTrashed()->where('pesantren_id', $this->pesantrenId)->where('nis', $nis)->exists()) {
                $summary['duplikat']++;
                continue;
            }

            if (! $this->isStatusNonAktif($row['status'] ?? null)) {
                if ($akanMenambahAktif >= $sisaKuota) {
                    $summary['melebihi_kuota']++;
                    continue;
                }
                $akanMenambahAktif++;
            }

            $waliEmail = $this->extractValidWaliEmail($row);
            if ($waliEmail !== null && ! array_key_exists($waliEmail, $waliSeen)) {
                $waliSeen[$waliEmail] = true;
                if ($this->classifyWaliEmail($waliEmail)['status'] === 'not_found') {
                    $summary['wali_baru']++;
                }
            }

            $summary['akan_diimpor']++;
        }

        return $summary;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;

            $nis         = trim((string) ($row['nis'] ?? ''));
            $namaLengkap = trim((string) ($row['nama_lengkap'] ?? ''));

            if ($nis === '' || $namaLengkap === '') {
                $this->errors[] = "Baris {$rowNum}: NIS dan Nama Lengkap wajib diisi.";
                $this->skipped++;
                continue;
            }

            if (Santri::withTrashed()->where('pesantren_id', $this->pesantrenId)->where('nis', $nis)->exists()) {
                $this->errors[] = "Baris {$rowNum}: NIS '{$nis}' sudah pernah terdaftar (termasuk data yang dihapus), dilewati.";
                $this->skipped++;
                continue;
            }

            $tanggalLahir = $this->parseTanggal($row['tanggal_lahir'] ?? null, $rowNum);
            $jenisKelamin = $this->resolveJenisKelamin($row['jenis_kelamin'] ?? null, $rowNum);
            $kelasId      = $this->resolveKelas($row['kelas'] ?? null, $rowNum);
            $kamarId      = $this->resolveKamar($row['kamar'] ?? null, $rowNum);
            $statusAktif  = $this->resolveStatusAktif($row['status'] ?? null, $rowNum);
            $waliSantriId = $this->resolveWali($row, $rowNum);

            try {
                Santri::create([
                    'pesantren_id'   => $this->pesantrenId,
                    'nis'            => $nis,
                    'nama_lengkap'   => $namaLengkap,
                    'nama_panggilan' => $this->nullable($row['nama_panggilan'] ?? null),
                    'tanggal_lahir'  => $tanggalLahir,
                    'jenis_kelamin'  => $jenisKelamin,
                    'nama_ayah'      => $this->nullable($row['nama_ayah'] ?? null),
                    'nama_ibu'       => $this->nullable($row['nama_ibu'] ?? null),
                    'alamat_lengkap' => $this->nullable($row['alamat_lengkap'] ?? null),
                    'jumlah_saudara' => is_numeric($row['jumlah_saudara'] ?? null) ? (int) $row['jumlah_saudara'] : null,
                    'cita_cita'      => $this->nullable($row['cita_cita'] ?? null),
                    'kelas_id'       => $kelasId,
                    'kamar_id'       => $kamarId,
                    'status_aktif'   => $statusAktif,
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

    private function parseTanggal(mixed $value, int $rowNum): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value);
                return \Carbon\Carbon::instance($date)->format('Y-m-d');
            }

            return \Carbon\Carbon::createFromFormat('d/m/Y', trim((string) $value))->format('Y-m-d');
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

        $nama     = trim((string) $value);
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

        $nama     = trim((string) $value);
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
        $nama     = $this->nullable($row['wali_nama'] ?? null);
        $noHp     = $this->nullable($row['wali_no_hp'] ?? null);
        $emailRaw = $this->nullable($row['wali_email'] ?? null);

        if ($nama === null && $emailRaw === null && $noHp === null) {
            return null;
        }

        if ($emailRaw === null) {
            $this->errors[] = "Baris {$rowNum}: Data wali diisi tapi wali_email kosong, wali tidak ditautkan (santri tetap dibuat).";
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
                'name'         => $nama ?? $emailRaw,
                'email'        => $emailRaw,
                'phone_number' => $noHp,
                'password'     => Str::password(12),
                'role'         => UserRole::WaliSantri->value,
            ]);

            return $this->waliCache[$cacheKey] = $user->id;
        } catch (\Throwable $e) {
            $this->errors[] = "Baris {$rowNum}: Gagal membuat akun wali baru untuk '{$emailRaw}' ({$e->getMessage()}).";
            return $this->waliCache[$cacheKey] = null;
        }
    }

    private function nullable(mixed $value): ?string
    {
        $str = trim((string) ($value ?? ''));
        return $str !== '' ? $str : null;
    }
}
