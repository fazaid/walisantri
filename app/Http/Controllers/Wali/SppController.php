<?php

namespace App\Http\Controllers\Wali;

use App\Enums\StatusTagihanSpp;
use App\Http\Controllers\Controller;
use App\Models\TagihanSpp;
use Illuminate\Http\Request;

class SppController extends Controller
{
    public function index()
    {
        $wali = auth()->user();

        $santris = $wali->anakSantri()->with([
            'tagihanSpp' => fn ($q) => $q->with('pembayaran')
                ->orderByDesc('tahun')
                ->orderByDesc('bulan'),
            'kelas',
        ])->get();

        $totalTunggakan = $santris->sum(
            fn ($s) => $s->tagihanSpp->where('status', StatusTagihanSpp::BelumBayar)->count()
        );

        $rekening = $wali->pesantren?->profil['rekening'] ?? [];

        return view('wali.spp.index', compact('santris', 'totalTunggakan', 'rekening'));
    }

    public function konfirmasi(Request $request, TagihanSpp $tagihan)
    {
        // Pastikan tagihan ini milik santri wali yang login
        $waliSantriIds = auth()->user()->anakSantri()->pluck('id');
        abort_unless($waliSantriIds->contains($tagihan->santri_id), 403);
        abort_unless($tagihan->status === StatusTagihanSpp::BelumBayar, 422, 'Status tagihan tidak valid.');

        $request->validate([
            'bukti_transfer' => ['required', 'image', 'max:5120'], // maks 5 MB
        ], [
            'bukti_transfer.required' => 'Bukti transfer wajib diunggah.',
            'bukti_transfer.image'    => 'File harus berupa gambar (JPG, PNG, dll).',
            'bukti_transfer.max'      => 'Ukuran file maksimal 5 MB.',
        ]);

        $path = $request->file('bukti_transfer')->store('bukti-spp', 'public');

        $tagihan->update([
            'bukti_transfer'       => $path,
            'dikonfirmasi_wali_at' => now(),
            'status'               => StatusTagihanSpp::MenungguKonfirmasi,
        ]);

        return back()->with('sukses_tagihan', $tagihan->id);
    }
}
