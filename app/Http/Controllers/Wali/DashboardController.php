<?php

// File: app/Http/Controllers/Wali/DashboardController.php

namespace App\Http\Controllers\Wali;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $wali = auth()->user();

        // Ambil semua anak santri milik wali ini
        $anakList = $wali->anakSantri()
            ->with(['pesantren', 'pembimbing'])
            ->where('status_aktif', true)
            ->get();

        // Jika hanya 1 anak, langsung redirect ke detail
        if ($anakList->count() === 1) {
            return redirect()->route('wali.santri.show', $anakList->first()->id);
        }

        return view('wali.dashboard', compact('wali', 'anakList'));

        // Ambil semua anak santri milik wali ini
        $anakList = $wali->anakSantri()
            ->with(['pesantren', 'pembimbing'])
            ->where('status_aktif', true)
            ->get();

        // Jika hanya 1 anak, langsung redirect ke detail
        if ($anakList->count() === 1) {
            return redirect()->route('wali.santri.show', $anakList->first()->id);
        }

        return view('wali.dashboard', compact('wali', 'anakList'));
    }
}