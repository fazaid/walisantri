{{-- Footer situs publik — dipakai landing dan /panduan. Lihat catatan
     $anchorBase, $registrationOpen, dan $demoOpen di partials/situs-nav. --}}
@php($anchorBase = $anchorBase ?? '')

<footer class="border-t border-gray-100 py-10">
    <div class="max-w-6xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-4">
        <a href="{{ $anchorBase ?: '/' }}" class="flex items-center gap-2 text-teal-700 font-bold text-lg">
            <img src="{{ \App\Models\PlatformBrandingSetting::logoUrl() }}" alt="Walisantri.com" class="h-7 w-auto">
            Walisantri.com
        </a>
        <p class="text-sm text-gray-400 text-center">
            Menghubungkan Pesantren & Wali Santri di Seluruh Indonesia
        </p>
        <div class="flex gap-6">
            <a href="{{ $anchorBase }}#harga" class="text-sm text-gray-500 hover:text-teal-700">Harga</a>
            <a href="{{ $anchorBase }}#faq" class="text-sm text-gray-500 hover:text-teal-700">FAQ</a>
            @if($registrationOpen)
                <a href="{{ route('register') }}" class="text-sm text-gray-500 hover:text-teal-700">Daftar</a>
            @elseif($demoOpen)
                <a href="{{ route('demo') }}" class="text-sm text-gray-500 hover:text-teal-700">Demo</a>
            @endif
            <a href="{{ route('login') }}" class="text-sm text-gray-500 hover:text-teal-700">Masuk</a>
        </div>
    </div>
    <div class="text-center mt-6 text-xs text-gray-400">
        © {{ date('Y') }} Walisantri.com · Hak Cipta Dilindungi
    </div>
</footer>
