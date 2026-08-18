<?php

namespace App\Http\Controllers;

use App\Models\Wilayah;
use Illuminate\Http\JsonResponse;

/**
 * Memberi makan dropdown wilayah berjenjang di halaman /register (§4.1):
 * satu permintaan per turunnya satu tingkat.
 *
 * Tanpa induk = daftar provinsi. Kode desa tidak pernah sampai ke sini — regex
 * rutenya sudah menolaknya, karena desa tidak punya anak.
 */
class WilayahController extends Controller
{
    public function __invoke(?string $parent = null): JsonResponse
    {
        $anak = Wilayah::anak($parent);

        // Datanya identik untuk semua pengunjung dan hanya berubah saat Kemendagri
        // merilis dataset baru (lalu `wilayah:impor` dijalankan) — jadi aman di-cache
        // bersama. max-age dibatasi sehari, bukan `immutable`, supaya pemekaran wilayah
        // menyebar sendiri tanpa cache-busting.
        return response()
            ->json($anak)
            ->header('Cache-Control', 'public, max-age=86400');
    }
}
