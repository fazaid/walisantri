{{--
    Kerangka bersama seluruh email platform (PRD §12.2).

    Semuanya HTML + gaya inline: klien email (Gmail, Outlook) membuang <style>
    di <head> dan tidak mengenal kelas CSS eksternal, jadi tabel + atribut style
    langsung adalah satu-satunya yang bisa diandalkan.

    Logo hanya ditampilkan bila berkasnya raster. Bawaan platform berformat SVG,
    dan Gmail tidak merender SVG sama sekali — lebih baik jatuh ke wordmark teks
    daripada mengirim kotak rusak.

    Dipakai lewat @extends('mail.layout', ['judul' => ...]) + @section('isi'),
    mengikuti konvensi layout lain di repo ini (wali.layouts.app).
    Variabel opsional: $aksiUrl + $aksiLabel (tombol), $penutup (paragraf akhir).
--}}
@php
    $logo = \App\Models\PlatformBrandingSetting::get('logo');
    $logoUrl = $logo && \Illuminate\Support\Str::endsWith(strtolower($logo), ['.png', '.jpg', '.jpeg'])
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($logo)
        : null;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $judul }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;color:#374151;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="background:#0f766e;padding:20px 28px;">
                            @if ($logoUrl)
                                <img src="{{ $logoUrl }}" alt="Walisantri.com" height="32" style="display:block;height:32px;border:0;">
                            @else
                                <span style="color:#ffffff;font-size:18px;font-weight:bold;letter-spacing:0.2px;">Walisantri.com</span>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px;">
                            <h1 style="margin:0 0 16px;font-size:20px;line-height:1.35;color:#111827;">{{ $judul }}</h1>

                            <div style="font-size:15px;line-height:1.65;color:#374151;">
                                @yield('isi')
                            </div>

                            @isset($aksiUrl)
                                <table role="presentation" cellpadding="0" cellspacing="0" style="margin:28px 0 4px;">
                                    <tr>
                                        <td style="background:#0f766e;border-radius:8px;">
                                            <a href="{{ $aksiUrl }}"
                                               style="display:inline-block;padding:12px 24px;color:#ffffff;font-size:15px;font-weight:bold;text-decoration:none;">
                                                {{ $aksiLabel ?? 'Buka Halaman' }}
                                            </a>
                                        </td>
                                    </tr>
                                </table>

                                {{-- Sebagian klien email memblokir tombol; tautannya harus tetap bisa disalin. --}}
                                <p style="margin:12px 0 0;font-size:12px;line-height:1.6;color:#9ca3af;word-break:break-all;">
                                    Bila tombol di atas tidak berfungsi, salin tautan ini ke peramban:<br>{{ $aksiUrl }}
                                </p>
                            @endisset

                            @isset($penutup)
                                <p style="margin:24px 0 0;font-size:14px;line-height:1.65;color:#6b7280;">{{ $penutup }}</p>
                            @endisset
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:18px 28px;background:#f9fafb;border-top:1px solid #e5e7eb;">
                            <p style="margin:0;font-size:12px;line-height:1.6;color:#9ca3af;">
                                Walisantri.com · Platform Digitalisasi Pesantren<br>
                                Email ini dikirim otomatis — mohon tidak membalas ke alamat ini.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
