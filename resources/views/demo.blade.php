<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Daftar waiting list demo Walisantri.com — platform digitalisasi pesantren Indonesia.">
    <title>Waiting List Demo — Walisantri.com</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-800 font-sans min-h-screen">

    {{-- Nav --}}
    <nav class="bg-white border-b border-gray-100">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ route('landing') }}" class="text-teal-700 font-bold text-xl tracking-tight">🕌 Walisantri.com</a>
            <a href="{{ route('landing') }}" class="text-sm text-gray-500 hover:text-teal-700">← Kembali ke Beranda</a>
        </div>
    </nav>

    <div class="max-w-2xl mx-auto px-6 py-16">

        @if(session('success'))
            {{-- Success state --}}
            <div class="text-center py-12">
                <div class="w-20 h-20 bg-teal-100 rounded-full flex items-center justify-center text-4xl mx-auto mb-6">
                    ✅
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-4">Pendaftaran Berhasil!</h1>
                <p class="text-gray-500 leading-relaxed mb-8">
                    Terima kasih! Tim Walisantri akan menghubungi Anda dalam <strong>1–2 hari kerja</strong>
                    untuk menjadwalkan sesi demo eksklusif.
                </p>
                <a href="{{ route('landing') }}"
                   class="inline-block bg-teal-700 text-white font-semibold px-8 py-3 rounded-xl hover:bg-teal-800 transition-colors">
                    Kembali ke Beranda
                </a>
            </div>

        @else
            {{-- Form --}}
            <div class="text-center mb-10">
                <div class="inline-block bg-teal-100 text-teal-800 text-xs font-semibold px-3 py-1 rounded-full mb-4 uppercase tracking-wide">
                    Waiting List Demo
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-4">Minta Demo Eksklusif</h1>
                <p class="text-gray-500 leading-relaxed">
                    Daftarkan pesantren Anda dan tim kami akan menghubungi untuk menjadwalkan
                    sesi demo langsung — gratis, tanpa komitmen.
                </p>
            </div>

            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm" style="padding: 2rem;">

                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 rounded-xl mb-6" style="padding: 1rem;">
                        <ul class="text-sm text-red-700 space-y-1">
                            @foreach($errors->all() as $error)
                                <li>• {{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('demo.submit') }}" style="display: flex; flex-direction: column; gap: 1.25rem;">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700" style="margin-bottom: 0.375rem;">
                                Nama Pesantren <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="nama_pesantren" value="{{ old('nama_pesantren') }}"
                                   placeholder="Pesantren Al-Falah"
                                   class="w-full border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-teal-400 focus:ring-1 focus:ring-teal-400 @error('nama_pesantren') border-red-400 @enderror"
                                   style="padding: 0.625rem 1rem;"
                                   required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700" style="margin-bottom: 0.375rem;">
                                Kota/Kabupaten
                            </label>
                            <input type="text" name="kota" value="{{ old('kota') }}"
                                   placeholder="Bandung"
                                   class="w-full border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-teal-400 focus:ring-1 focus:ring-teal-400"
                                   style="padding: 0.625rem 1rem;">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700" style="margin-bottom: 0.375rem;">
                            Nama Kontak / PIC <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="nama_kontak" value="{{ old('nama_kontak') }}"
                               placeholder="Ust. Ahmad Fauzi"
                               class="w-full border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-teal-400 focus:ring-1 focus:ring-teal-400 @error('nama_kontak') border-red-400 @enderror"
                               style="padding: 0.625rem 1rem;"
                               required>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700" style="margin-bottom: 0.375rem;">
                                Email <span class="text-red-500">*</span>
                            </label>
                            <input type="email" name="email" value="{{ old('email') }}"
                                   placeholder="admin@pesantren.id"
                                   class="w-full border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-teal-400 focus:ring-1 focus:ring-teal-400 @error('email') border-red-400 @enderror"
                                   style="padding: 0.625rem 1rem;"
                                   required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700" style="margin-bottom: 0.375rem;">
                                No. HP / WhatsApp <span class="text-red-500">*</span>
                            </label>
                            <input type="tel" name="no_hp" value="{{ old('no_hp') }}"
                                   placeholder="08123456789"
                                   class="w-full border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-teal-400 focus:ring-1 focus:ring-teal-400 @error('no_hp') border-red-400 @enderror"
                                   style="padding: 0.625rem 1rem;"
                                   required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700" style="margin-bottom: 0.375rem;">
                            Jumlah Santri (perkiraan)
                        </label>
                        <select name="jumlah_santri"
                                class="w-full border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-teal-400 focus:ring-1 focus:ring-teal-400 bg-white"
                                style="padding: 0.625rem 1rem;">
                            <option value="">-- Pilih rentang --</option>
                            @foreach([
                                '< 50 santri',
                                '50–100 santri',
                                '100–300 santri',
                                '300–500 santri',
                                '500–1.000 santri',
                                '> 1.000 santri',
                            ] as $opt)
                                <option value="{{ $opt }}" @selected(old('jumlah_santri') === $opt)>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700" style="margin-bottom: 0.375rem;">
                            Fitur yang Paling Dibutuhkan
                        </label>
                        <textarea name="catatan" rows="3"
                                  placeholder="Contoh: kami sangat butuh modul mutaba'ah dan portal wali santri..."
                                  class="w-full border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-teal-400 focus:ring-1 focus:ring-teal-400 resize-none"
                                  style="padding: 0.625rem 1rem;">{{ old('catatan') }}</textarea>
                    </div>

                    <button type="submit"
                            class="w-full bg-teal-700 text-white font-semibold rounded-xl hover:bg-teal-800 transition-colors text-sm"
                            style="padding: 0.75rem 1rem;">
                        Daftar Waiting List Demo →
                    </button>

                    <p class="text-xs text-gray-400 text-center">
                        Data Anda aman dan tidak akan disebarkan. Tim kami menghubungi dalam 1–2 hari kerja.
                    </p>
                </form>
            </div>

            {{-- Benefit reminder --}}
            <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
                @foreach([
                    ['🎯', 'Demo Personal', 'Sesi 1-on-1 bersama tim kami'],
                    ['⚡', 'Setup Gratis', 'Kami bantu setup awal pesantren Anda'],
                    ['💬', 'Konsultasi', 'Tanya apapun tentang digitalisasi pesantren'],
                ] as $benefit)
                    <div class="bg-white border border-gray-100 rounded-xl p-4">
                        <div class="text-2xl mb-2">{{ $benefit[0] }}</div>
                        <div class="font-semibold text-gray-800 text-sm mb-1">{{ $benefit[1] }}</div>
                        <div class="text-xs text-gray-500">{{ $benefit[2] }}</div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>

    <footer class="border-t border-gray-100 py-8 text-center text-sm text-gray-400 mt-10">
        © {{ date('Y') }} Walisantri.com · Platform Digitalisasi Pesantren Indonesia
    </footer>

</body>
</html>
