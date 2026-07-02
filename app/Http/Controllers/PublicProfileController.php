<?php

namespace App\Http\Controllers;

use App\Models\Pesantren;
use Illuminate\Http\Request;

class PublicProfileController extends Controller
{
    public function index(Request $request)
    {
        /** @var Pesantren $pesantren */
        $pesantren = $request->attributes->get('public_pesantren');

        $loginUrl = route('login') . '?tenant=' . $pesantren->slug;

        return view('public.profile', compact('pesantren', 'loginUrl'));
    }

    // Placeholder — menu tersedia di nav, fitur penuh direncanakan pasca-MVP (§1.4)
    public function kegiatan(Request $request)
    {
        return $this->comingSoon($request, 'Kegiatan Pesantren');
    }

    public function artikel(Request $request)
    {
        return $this->comingSoon($request, 'Artikel');
    }

    private function comingSoon(Request $request, string $menu)
    {
        /** @var Pesantren $pesantren */
        $pesantren = $request->attributes->get('public_pesantren');

        $loginUrl = route('login') . '?tenant=' . $pesantren->slug;

        return view('public.coming-soon', compact('pesantren', 'loginUrl', 'menu'));
    }
}
