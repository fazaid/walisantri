{{-- Header profil publik — dipakai beranda & halaman placeholder (§1.4) --}}
<header class="bg-teal-700 text-white">
    <div class="max-w-3xl mx-auto px-6 py-8 flex items-center gap-5">
        @if($pesantren->logo_url)
            <img src="{{ $pesantren->logo_url }}"
                 alt="Logo {{ $pesantren->nama_pesantren }}"
                 class="w-16 h-16 rounded-xl object-cover shadow-md">
        @else
            <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center text-3xl">🕌</div>
        @endif
        <div>
            <h1 class="text-2xl font-bold">{{ $pesantren->nama_pesantren }}</h1>
            @if($pesantren->profil['alamat'] ?? null)
                <p class="text-teal-200 text-sm mt-1">📍 {{ $pesantren->profil['alamat'] }}</p>
            @endif
        </div>
    </div>

    {{-- Nav --}}
    <div class="border-t border-teal-600">
        <div class="max-w-3xl mx-auto px-6 flex gap-6 text-sm py-3">
            <a href="{{ url('/') }}" class="{{ request()->is('/') ? 'text-white font-medium' : 'text-teal-200 hover:text-white' }}">Beranda</a>
            <a href="{{ url('/kegiatan') }}" class="{{ request()->is('kegiatan') ? 'text-white font-medium' : 'text-teal-200 hover:text-white' }}">Kegiatan Pesantren</a>
            <a href="{{ url('/artikel') }}" class="{{ request()->is('artikel') ? 'text-white font-medium' : 'text-teal-200 hover:text-white' }}">Artikel</a>
            <a href="{{ $loginUrl }}" class="ml-auto bg-white text-teal-700 font-semibold px-4 py-1.5 rounded-lg text-sm hover:bg-teal-50">
                Portal Wali Santri →
            </a>
        </div>
    </div>
</header>
