<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pesantren · Walisantri.com</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen py-12 px-4">

    <div class="max-w-lg mx-auto">
        <div class="text-center mb-8">
            <a href="{{ route('landing') }}" class="text-teal-700 font-bold text-xl">🕌 Walisantri.com</a>
            <h1 class="text-2xl font-bold text-gray-900 mt-4">Daftarkan Pesantren Anda</h1>
            <p class="text-gray-500 text-sm mt-1">Trial 30 hari gratis — mulai hubungkan pesantren Anda dengan wali santri hari ini</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3 mb-5">
                    <ul class="space-y-1">
                        @foreach($errors->all() as $error)
                            <li>• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register.submit') }}" class="space-y-5">
                @csrf

                {{-- Nama Pesantren --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Pesantren</label>
                    <input type="text" name="nama_pesantren" value="{{ old('nama_pesantren') }}"
                           required autofocus
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"
                           placeholder="Pesantren Al-Hidayah">
                </div>

                {{-- Slug --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Subdomain</label>
                    <div class="flex rounded-xl border border-gray-300 overflow-hidden focus-within:ring-2 focus-within:ring-teal-500">
                        <input type="text" name="slug" id="slug" value="{{ old('slug') }}"
                               required
                               class="flex-1 px-4 py-2.5 text-sm outline-none bg-white"
                               placeholder="al-hidayah"
                               pattern="[a-z0-9][a-z0-9\-]{1,28}[a-z0-9]">
                        <span class="bg-gray-50 px-3 py-2.5 text-sm text-gray-500 border-l border-gray-300">
                            .walisantri.com
                        </span>
                    </div>
                    <p id="slug-status" class="text-xs mt-1 text-gray-400">3–30 karakter, huruf kecil, angka, tanda hubung</p>
                </div>

                <hr class="border-gray-100">

                {{-- Nama Admin --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Anda (Admin)</label>
                    <input type="text" name="admin_name" value="{{ old('admin_name') }}"
                           required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"
                           placeholder="Nama lengkap">
                </div>

                {{-- Email --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"
                           placeholder="admin@pesantren.com">
                </div>

                {{-- Password --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" required minlength="8"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"
                           placeholder="Minimal 8 karakter">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password</label>
                    <input type="password" name="password_confirmation" required minlength="8"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500"
                           placeholder="Ulangi password">
                </div>

                <button type="submit"
                        class="w-full bg-teal-700 hover:bg-teal-800 text-white font-semibold py-2.5 rounded-xl transition-colors">
                    Daftarkan Pesantren
                </button>

                <p class="text-center text-xs text-gray-400">
                    Dengan mendaftar Anda menyetujui
                    <span class="underline cursor-pointer">syarat & ketentuan</span> Walisantri.com.
                </p>

            </form>
        </div>

        <p class="text-center text-sm text-gray-500 mt-5">
            Sudah punya akun?
            <a href="{{ route('login') }}" class="text-teal-700 font-medium hover:underline">Masuk</a>
        </p>
    </div>

    <script>
    const slugInput = document.getElementById('slug');
    const statusEl  = document.getElementById('slug-status');
    let timer;

    slugInput?.addEventListener('input', () => {
        clearTimeout(timer);
        const val = slugInput.value;
        if (val.length < 3) { statusEl.textContent = '3–30 karakter, huruf kecil, angka, tanda hubung'; statusEl.className = 'text-xs mt-1 text-gray-400'; return; }
        statusEl.textContent = 'Memeriksa…'; statusEl.className = 'text-xs mt-1 text-gray-400';
        timer = setTimeout(async () => {
            try {
                const r = await fetch(`/check-slug/${encodeURIComponent(val)}`);
                const d = await r.json();
                statusEl.textContent = d.message;
                statusEl.className = `text-xs mt-1 ${d.available ? 'text-green-600' : 'text-red-500'}`;
            } catch { statusEl.textContent = 'Gagal memeriksa slug.'; }
        }, 400);
    });
    </script>

</body>
</html>
