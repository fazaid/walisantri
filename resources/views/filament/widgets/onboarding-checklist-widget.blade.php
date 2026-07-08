<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">🚀 Langkah Awal Setup Pesantren</x-slot>

        <x-slot name="description">
            {{ $requiredDone }}/{{ $requiredTotal }} langkah wajib selesai
        </x-slot>

        <div class="space-y-2">
            @foreach ($items as $item)
                <div class="flex items-center gap-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3">
                    @if ($item['done'])
                        <x-heroicon-s-check-circle class="w-5 h-5 shrink-0 text-success-500" />
                    @else
                        <div class="w-5 h-5 shrink-0 rounded-full border-2 border-gray-300 dark:border-gray-600"></div>
                    @endif

                    <div class="flex-1 min-w-0">
                        <span @class([
                            'text-sm',
                            'text-gray-500 dark:text-gray-400 line-through' => $item['done'],
                            'text-gray-900 dark:text-white font-medium' => ! $item['done'],
                        ])>
                            {{ $item['label'] }}
                        </span>

                        @unless ($item['required'])
                            <span class="ml-2 inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2 py-0.5 text-xs text-gray-500 dark:text-gray-400">
                                Opsional
                            </span>
                        @endunless
                    </div>

                    @unless ($item['done'])
                        <a href="{{ $item['url'] }}" class="text-xs text-primary-600 dark:text-primary-400 font-medium shrink-0">
                            Mulai &rarr;
                        </a>
                    @endunless
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
