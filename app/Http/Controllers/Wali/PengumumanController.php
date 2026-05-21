<?php

namespace App\Http\Controllers\Wali;

use App\Http\Controllers\Controller;
use App\Models\MasterPengumuman;
use App\Models\MasterPengumumanCentral;
use Illuminate\Support\Collection;

class PengumumanController extends Controller
{
    public function index()
    {
        // Pengumuman dari pesantren — Multitenantable auto-scope ke pesantren_id wali
        $pesantrenItems = MasterPengumuman::orderByDesc('created_at')
            ->get()
            ->map(fn ($p) => (object) [
                'judul'       => $p->judul_maklumat,
                'isi'         => $p->isi_maklumat,
                'created_at'  => $p->created_at,
                'badge'       => 'Pesantren',
                'badge_color' => 'teal',
            ]);

        // Pengumuman dari pusat — global (tidak terikat tenant)
        $centralItems = MasterPengumumanCentral::where('is_active', true)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($p) => (object) [
                'judul'       => $p->judul_maklumat,
                'isi'         => $p->isi_maklumat,
                'created_at'  => $p->created_at,
                'badge'       => 'Pusat',
                'badge_color' => 'purple',
            ]);

        $pengumuman = $pesantrenItems
            ->merge($centralItems)
            ->sortByDesc('created_at')
            ->values();

        return view('wali.pengumuman.index', compact('pengumuman'));
    }
}
