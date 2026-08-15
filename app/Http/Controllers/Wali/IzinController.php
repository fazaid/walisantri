<?php

namespace App\Http\Controllers\Wali;

use App\Enums\JenisIzin;
use App\Enums\StatusPengajuanIzin;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Wali\Concerns\ResolvesSantriMilikWali;
use App\Models\PresensiIzin;
use App\Models\PresensiPengaturan;
use App\Observers\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class IzinController extends Controller
{
    use ResolvesSantriMilikWali;

    public function index()
    {
        $anak = Auth::user()->anakSantri()->orderBy('nama_lengkap')->get();

        $daftar = PresensiIzin::withoutGlobalScope('pesantren')
            ->whereIn('santri_id', $anak->pluck('id'))
            ->with('santri')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('wali.izin.index', [
            'anak' => $anak,
            'daftar' => $daftar,
            'jenisOptions' => JenisIzin::options(),
            'bolehMengajukan' => $this->bolehMengajukan(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($this->bolehMengajukan(), 403, 'Pengajuan izin lewat portal sedang tidak diaktifkan.');

        $data = $request->validate([
            'santri_id' => ['required', 'integer'],
            'jenis' => ['required', 'string', 'in:'.implode(',', array_keys(JenisIzin::options()))],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'alasan' => ['required', 'string', 'max:1000'],
            'lampiran' => ['nullable', 'image', 'max:5120'],
        ], [
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.',
            'lampiran.image' => 'Lampiran harus berupa gambar (foto surat dokter).',
            'lampiran.max' => 'Ukuran lampiran maksimal 5 MB.',
        ]);

        // Kepemilikan WAJIB dicek eksplisit. Global scope hanya menyaring
        // pesantren_id, bukan wali_santri_id — mengandalkannya adalah persis
        // bug §8 #1 yang sudah pernah terjadi di halaman rapor wali.
        // pastikanSantriMilikWali (403), bukan findOrFail (404), supaya konsisten
        // dengan SppController::konfirmasi yang menerima santri_id dari wali juga.
        $this->pastikanSantriMilikWali((int) $data['santri_id']);
        $santri = $this->santriMilikWali((int) $data['santri_id'], []);

        $mulai = Carbon::parse($data['tanggal_mulai'])->toDateString();
        $selesai = Carbon::parse($data['tanggal_selesai'])->toDateString();

        if (PresensiIzin::beririsan($santri->id, $mulai, $selesai)->exists()) {
            return back()
                ->withInput()
                ->withErrors(['tanggal_mulai' => 'Sudah ada pengajuan izin untuk anak ini pada rentang tanggal tersebut.']);
        }

        // ⚠️ Disk 'local', BUKAN 'public'. Surat keterangan dokter adalah data
        // kesehatan anak (§13.2), dan disk public menghasilkan URL yang bisa
        // ditebak tanpa pernah melewati otorisasi.
        $lampiran = $request->hasFile('lampiran')
            ? $request->file('lampiran')->store('izin-santri', 'local')
            : null;

        $izin = PresensiIzin::withoutGlobalScope('pesantren')->create([
            'pesantren_id' => $santri->pesantren_id,
            'santri_id' => $santri->id,
            'jenis' => $data['jenis'],
            'tanggal_mulai' => $mulai,
            'tanggal_selesai' => $selesai,
            'alasan' => $data['alasan'],
            'lampiran' => $lampiran,
            'status' => StatusPengajuanIzin::Diajukan,
            'diajukan_oleh' => Auth::id(),
        ]);

        ActivityLogger::log('presensi.izin_diajukan', $izin, null, [
            'santri_id' => $santri->id,
            'jenis' => $data['jenis'],
        ]);

        return back()->with('sukses_izin', $izin->id);
    }

    /** Lampiran disajikan lewat rute terotorisasi — pola orders.bukti-transfer. */
    public function lampiran(PresensiIzin $izin)
    {
        $this->pastikanSantriMilikWali($izin->santri_id);

        abort_unless($izin->lampiran && Storage::disk('local')->exists($izin->lampiran), 404);

        return Storage::disk('local')->response($izin->lampiran);
    }

    /**
     * Bolehkah wali yang sedang login mengajukan izin?
     *
     * Pemeriksaan sesi Magic Link di sini adalah lapis KEDUA dan sengaja
     * dipertahankan meski praktis tidak pernah tercapai: middleware
     * BlockMagicLinkSession sudah mengalihkan sesi magic link dari seluruh
     * halaman portal agregat (termasuk /wali/izin) kembali ke halaman report.
     * Kalau suatu saat rute ini dimasukkan ke ROUTE_DIIZINKAN supaya wali bisa
     * MEMBACA riwayat izinnya lewat tautan cepat, penjagaan ini yang menahan
     * formnya tetap tersembunyi.
     */
    private function bolehMengajukan(): bool
    {
        if (session('magic_link_session')) {
            return false;
        }

        $pesantrenId = Auth::user()?->pesantren_id;

        return $pesantrenId ? PresensiPengaturan::untuk($pesantrenId)->izin_wali_aktif : false;
    }
}
