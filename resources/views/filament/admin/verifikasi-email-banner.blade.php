{{-- Spanduk verifikasi email (§12.2) — MENGINGATKAN, bukan memblokir.

     Dipasang lewat renderHook(PAGE_START) supaya tampil di seluruh halaman panel,
     bukan sebagai widget dashboard: alamat yang belum terbukti hidup harus terlihat
     di mana pun admin berada, bukan hanya saat kebetulan membuka dashboard.

     Merender kosong untuk semua orang selain admin pesantren yang emailnya belum
     dikonfirmasi. Ustadz & wali sengaja dilewati — alamat mereka diketik admin,
     dan belum ada satu pun email platform yang menyasar mereka. --}}
@php
    $pengguna = auth()->user();
    $perluKonfirmasi = $pengguna
        && $pengguna->role === \App\Enums\UserRole::AdminPesantren->value
        && filled($pengguna->email)
        && ! $pengguna->hasVerifiedEmail();
@endphp

@if ($perluKonfirmasi)
    <div style="
            display:flex; flex-wrap:wrap; align-items:center; gap:.75rem;
            margin-bottom:1rem; padding:.75rem 1rem; border-radius:.75rem;
            background:var(--warning-50); border:1px solid var(--warning-300);
        "
        class="dark:!bg-transparent dark:!border-amber-500/30"
    >
        <x-heroicon-o-envelope class="w-5 h-5 shrink-0 text-amber-600 dark:text-amber-400" />

        <div style="flex:1 1 16rem; min-width:0;" class="text-sm text-amber-900 dark:text-amber-200">
            <strong>Alamat email {{ $pengguna->email }} belum dikonfirmasi.</strong>
            Tagihan dan peringatan masa aktif langganan dikirim ke sana — kalau alamatnya keliru,
            pemberitahuan itu tidak akan pernah sampai.
        </div>

        <form method="POST" action="{{ route('verification.send') }}" style="flex:0 0 auto;">
            @csrf
            <button type="submit"
                    class="text-sm font-medium rounded-lg px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white">
                Kirim ulang tautan
            </button>
        </form>
    </div>
@endif
