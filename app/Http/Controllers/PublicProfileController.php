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

        // Host pesantren melayani /login sendiri sejak §1.8 Fase 1, jadi tautan ini
        // tidak boleh lagi melempar wali ke app.walisantri.com — persis kebocoran
        // merek yang §1.8 ada untuk menutupnya, dan di titik paling terlihat pula.
        // ?tenant= juga tidak perlu lagi: brandingnya diturunkan dari host.
        $loginUrl = $pesantren->url('/login');

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

        // Host pesantren melayani /login sendiri sejak §1.8 Fase 1, jadi tautan ini
        // tidak boleh lagi melempar wali ke app.walisantri.com — persis kebocoran
        // merek yang §1.8 ada untuk menutupnya, dan di titik paling terlihat pula.
        // ?tenant= juga tidak perlu lagi: brandingnya diturunkan dari host.
        $loginUrl = $pesantren->url('/login');

        return view('public.coming-soon', compact('pesantren', 'loginUrl', 'menu'));
    }
}
