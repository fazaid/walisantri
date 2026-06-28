<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">📢 Pengumuman dari Pusat</x-slot>

        <div class="space-y-3">
            @foreach ($pengumuman as $item)
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                    <div class="font-semibold text-gray-900 dark:text-white text-sm">
                        {{ $item['judul'] }}
                    </div>
                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                        {{ $item['tanggal'] }}
                    </div>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                        {{ $item['isi'] }}
                    </p>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
