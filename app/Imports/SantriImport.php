<?php

namespace App\Imports;

use App\Models\Kamar;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Support\Collection;
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

    private array $userCache = [];

    public function __construct(
        private int $pesantrenId
    ) {}

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

            if (Santri::where('pesantren_id', $this->pesantrenId)->where('nis', $nis)->exists()) {
                $this->errors[] = "Baris {$rowNum}: NIS '{$nis}' sudah terdaftar, dilewati.";
                $this->skipped++;
                continue;
            }

            $tanggalLahir = $this->parseTanggal($row['tanggal_lahir'] ?? null, $rowNum);
            $kelasId      = $this->resolveKelas($row['kelas'] ?? null, $rowNum);
            $kamarId      = $this->resolveKamar($row['kamar'] ?? null, $rowNum);
            $waliId       = $this->resolveUser($row['email_wali'] ?? null, 'wali_santri', $rowNum, 'email_wali');
            $ustadzId     = $this->resolveUser($row['email_ustadz'] ?? null, 'ustadz', $rowNum, 'email_ustadz');

            Santri::create([
                'pesantren_id'        => $this->pesantrenId,
                'nis'                 => $nis,
                'nama_lengkap'        => $namaLengkap,
                'nama_panggilan'      => $this->nullable($row['nama_panggilan'] ?? null),
                'tanggal_lahir'       => $tanggalLahir,
                'nama_ayah'           => $this->nullable($row['nama_ayah'] ?? null),
                'nama_ibu'            => $this->nullable($row['nama_ibu'] ?? null),
                'alamat_lengkap'      => $this->nullable($row['alamat_lengkap'] ?? null),
                'jumlah_saudara'      => is_numeric($row['jumlah_saudara'] ?? null) ? (int) $row['jumlah_saudara'] : null,
                'cita_cita'           => $this->nullable($row['cita_cita'] ?? null),
                'kelas_id'            => $kelasId,
                'kamar_id'            => $kamarId,
                'wali_santri_id'      => $waliId,
                'pembimbing_ustadz_id'=> $ustadzId,
                'status_aktif'        => true,
            ]);

            $this->imported++;
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

            return \Carbon\Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Exception) {
            $this->errors[] = "Baris {$rowNum}: Format tanggal lahir '{$value}' tidak valid, kolom diabaikan.";
            return null;
        }
    }

    private function resolveKelas(mixed $value, int $rowNum): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $nama = trim((string) $value);

        if (! array_key_exists($nama, $this->kelasCache)) {
            $this->kelasCache[$nama] = Kelas::where('pesantren_id', $this->pesantrenId)
                ->where('nama_kelas', $nama)
                ->value('id');
        }

        if (! $this->kelasCache[$nama]) {
            $this->errors[] = "Baris {$rowNum}: Kelas '{$nama}' tidak ditemukan, kolom diabaikan.";
        }

        return $this->kelasCache[$nama];
    }

    private function resolveKamar(mixed $value, int $rowNum): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $nama = trim((string) $value);

        if (! array_key_exists($nama, $this->kamarCache)) {
            $this->kamarCache[$nama] = Kamar::where('pesantren_id', $this->pesantrenId)
                ->where('nama_kamar', $nama)
                ->value('id');
        }

        if (! $this->kamarCache[$nama]) {
            $this->errors[] = "Baris {$rowNum}: Kamar '{$nama}' tidak ditemukan, kolom diabaikan.";
        }

        return $this->kamarCache[$nama];
    }

    private function resolveUser(mixed $value, string $role, int $rowNum, string $col): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $email = trim((string) $value);
        $key   = "{$role}:{$email}";

        if (! array_key_exists($key, $this->userCache)) {
            $this->userCache[$key] = User::where('email', $email)
                ->where('role', $role)
                ->where('pesantren_id', $this->pesantrenId)
                ->value('id');
        }

        if (! $this->userCache[$key]) {
            $this->errors[] = "Baris {$rowNum}: Pengguna '{$email}' ({$col}) tidak ditemukan, kolom diabaikan.";
        }

        return $this->userCache[$key];
    }

    private function nullable(mixed $value): ?string
    {
        $str = trim((string) ($value ?? ''));
        return $str !== '' ? $str : null;
    }
}
