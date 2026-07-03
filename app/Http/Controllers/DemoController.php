<?php

namespace App\Http\Controllers;

use App\Models\DemoRequest;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class DemoController extends Controller
{
    /**
     * Submit lebih cepat dari ini dianggap bot, bukan manusia mengisi form.
     */
    private const MIN_FILL_SECONDS = 3;

    public function show()
    {
        return view('demo', [
            'formToken' => Crypt::encryptString((string) now()->timestamp),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_pesantren' => ['required', 'string', 'max:200'],
            'nama_kontak'    => ['required', 'string', 'max:200'],
            'email'          => ['required', 'email', 'max:200'],
            'no_hp'          => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{8,20}$/'],
            'jumlah_santri'  => ['nullable', 'string', 'max:50'],
            'kota'           => ['nullable', 'string', 'max:100'],
            'catatan'        => ['nullable', 'string', 'max:1000'],
        ], [
            'no_hp.regex' => 'Format No. HP tidak valid — gunakan angka saja.',
        ]);

        if ($this->isLikelyBot($request)) {
            // Pura-pura sukses supaya bot tidak tahu ditolak & berhenti mencoba lagi.
            return redirect()->route('demo')->with('success', true);
        }

        DemoRequest::create($validated);

        return redirect()->route('demo')->with('success', true);
    }

    private function isLikelyBot(Request $request): bool
    {
        // Honeypot: field ini harus selalu kosong untuk pengisi manusia.
        if (filled($request->input('website'))) {
            return true;
        }

        try {
            $renderedAt = (int) Crypt::decryptString((string) $request->input('form_token'));
        } catch (DecryptException) {
            return true;
        }

        return now()->timestamp - $renderedAt < self::MIN_FILL_SECONDS;
    }
}
