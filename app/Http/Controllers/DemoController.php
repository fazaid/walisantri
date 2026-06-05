<?php

namespace App\Http\Controllers;

use App\Models\DemoRequest;
use Illuminate\Http\Request;

class DemoController extends Controller
{
    public function show()
    {
        return view('demo');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_pesantren' => ['required', 'string', 'max:200'],
            'nama_kontak'    => ['required', 'string', 'max:200'],
            'email'          => ['required', 'email', 'max:200'],
            'no_hp'          => ['required', 'string', 'max:20'],
            'jumlah_santri'  => ['nullable', 'string', 'max:50'],
            'kota'           => ['nullable', 'string', 'max:100'],
            'catatan'        => ['nullable', 'string', 'max:1000'],
        ]);

        DemoRequest::create($validated);

        return redirect()->route('demo')->with('success', true);
    }
}
