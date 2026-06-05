<div
    x-data="{ copied: false }"
    class="px-1 pb-2"
>
    <p class="mb-3 text-sm text-gray-500">
        Salin link ini dan kirimkan ke wali santri via WhatsApp atau media lain.
        Link berlaku permanen sampai di-regenerasi.
    </p>

    <div class="flex items-center gap-2">
        <input
            x-ref="linkInput"
            type="text"
            readonly
            value="{{ $url }}"
            onclick="this.select()"
            class="flex-1 rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 font-mono text-xs text-gray-700 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
        >
        <button
            type="button"
            @click="
                navigator.clipboard.writeText($refs.linkInput.value);
                copied = true;
                setTimeout(() => copied = false, 2000)
            "
            class="shrink-0 rounded-lg bg-teal-600 px-3 py-2 text-sm font-medium text-white hover:bg-teal-700 focus:outline-none"
        >
            <span x-show="!copied">Salin</span>
            <span x-show="copied" x-cloak>Tersalin ✓</span>
        </button>
    </div>
</div>
