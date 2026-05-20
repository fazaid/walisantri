{{-- File: resources/views/wali/auth/login.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0f766e">
    <title>Login Wali Santri</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-teal-700 min-h-screen flex items-center justify-center px-4">

    <div class="w-full max-w-sm">

        {{-- Logo / Header --}}
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-lg">
                <span class="text-3xl">🕌</span>
            </div>
            <h1 class="text-white text-2xl font-bold">Portal Wali Santri</h1>
            <p class="text-teal-200 text-sm mt-1">{{ config('app.name') }}</p>
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
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                        placeholder="email@contoh.com"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input
                        type="password"
                        name="password"
                        required
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                        placeholder="••••••••"
                    >
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="remember" id="remember" class="rounded">
                    <label for="remember" class="text-sm text-gray-600">Ingat saya</label>
                </div>

                <button
                    type="submit"
                    class="w-full bg-teal-700 hover:bg-teal-800 text-white font-semibold py-2.5 rounded-xl transition-colors">
                    Masuk
                </button>
            </form>

        </div>

        <p class="text-center text-teal-200 text-xs mt-6">
            Untuk admin & ustadz, gunakan
            <a href="/admin/login" class="underline">panel admin</a>.
        </p>

    </div>

</body>
</html>