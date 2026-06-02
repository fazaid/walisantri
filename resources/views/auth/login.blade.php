{{-- Login terpusat app.walisantri.com/login (§1.3)
     ?tenant={slug} → tampilkan branding pesantren di halaman ini --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="{{ $pesantren ? '#1d4ed8' : '#0f766e' }}">
    <title>
        @if($pesantren)
            Portal Wali — {{ $pesantren->nama_pesantren }}
        @else
            Masuk · Walisantri.com
        @endif
    </title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex items-center justify-center px-4
    {{ $pesantren ? 'bg-blue-700' : 'bg-teal-700' }}">

    <div class="w-full max-w-sm">

        {{-- Header / Branding --}}
        <div class="text-center mb-8">
            @if($pesantren && ($pesantren->profil['logo'] ?? null))
                <img src="{{ $pesantren->profil['logo'] }}"
                     alt="Logo {{ $pesantren->nama_pesantren }}"
                     class="w-16 h-16 rounded-2xl object-cover mx-auto mb-3 shadow-lg">
            @else
                <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                    <span class="text-3xl">🕌</span>
                </div>
            @endif

            @if($pesantren)
                <h1 class="text-white text-xl font-bold">{{ $pesantren->nama_pesantren }}</h1>
                <p class="text-blue-200 text-sm mt-1">Portal Wali Santri</p>
            @else
                <h1 class="text-white text-2xl font-bold">Walisantri.com</h1>
                <p class="text-teal-200 text-sm mt-1">Masuk ke akun Anda</p>
            @endif
        </div>

        {{-- Card Form --}}
        <div class="bg-white rounded-2xl shadow-xl p-6">

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3 mb-4">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('wali.login.submit') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           required autofocus
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm
                                  focus:outline-none focus:ring-2 focus:ring-teal-500"
                           placeholder="email@contoh.com">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm
                                  focus:outline-none focus:ring-2 focus:ring-teal-500"
                           placeholder="••••••••">
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="remember" id="remember" class="rounded">
                    <label for="remember" class="text-sm text-gray-600">Ingat saya</label>
                </div>

                <button type="submit"
                        class="w-full font-semibold py-2.5 rounded-xl transition-colors text-white
                               {{ $pesantren ? 'bg-blue-700 hover:bg-blue-800' : 'bg-teal-700 hover:bg-teal-800' }}">
                    Masuk
                </button>
            </form>

        </div>

        <p class="text-center text-xs mt-6
            {{ $pesantren ? 'text-blue-200' : 'text-teal-200' }}">
            Admin & Ustadz masuk melalui tautan yang sama.<br>
            Wali santri? Gunakan Magic Link dari pesantren.
        </p>

    </div>

</body>
</html>
