<x-filament-panels::page>
@php
    $summary = $this->getSummary();
    $rows    = $this->getData();
    $baseUrl = url('/admin/keuangan/uang-sakus');
@endphp

{{-- Ringkasan --}}
<div class="grid grid-cols-2 gap-4 mb-6">
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Total Santri</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $summary->total_santri }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Total Saldo</p>
        <p class="text-2xl font-bold {{ $summary->total_saldo >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
            Rp {{ number_format($summary->total_saldo, 0, ',', '.') }}
        </p>
    </div>
</div>

{{-- Search --}}
<div class="mb-4">
    <input
        type="text"
        wire:model.live.debounce.300ms="search"
        placeholder="Cari nama santri..."
        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 shadow-sm placeholder-gray-400
               focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500
               dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder-gray-500"
    >
</div>

{{-- Kartu santri --}}
@if($rows->isEmpty())
    <div class="rounded-xl border border-gray-200 bg-white py-12 text-center text-sm text-gray-400 dark:border-gray-700 dark:bg-gray-900">
        {{ $search ? 'Tidak ada santri yang cocok dengan pencarian.' : 'Belum ada santri aktif.' }}
    </div>
@else
    <div class="space-y-3">
        @foreach($rows as $row)
            <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <p class="font-semibold text-gray-800 dark:text-gray-100 text-sm">{{ $row->nama_lengkap }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ $row->nama_kelas ?? '—' }}</p>
                    </div>
                    <p class="font-bold text-base ml-3 flex-shrink-0 {{ $row->saldo >= 0 ? 'text-green-700 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        Rp {{ number_format($row->saldo, 0, ',', '.') }}
                    </p>
                </div>
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>
                        <span class="text-green-700 dark:text-green-400">↑ Rp {{ number_format($row->total_setoran, 0, ',', '.') }}</span>
                        <span class="mx-1">·</span>
                        <span class="text-amber-700 dark:text-amber-400">↓ Rp {{ number_format($row->total_pengambilan, 0, ',', '.') }}</span>
                    </span>
                    <a href="{{ $baseUrl }}?filters[santri_id][value]={{ $row->id }}"
                       class="text-primary-600 dark:text-primary-400 hover:underline font-medium">
                        Transaksi →
                    </a>
                    {{-- href intentionally uses alias 'filters' (bukan 'tableFilters') sesuai #[Url(as:'filters')] di ListRecords --}}
                </div>
            </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    @if($rows->hasPages())
        <div class="mt-6">
            {{ $rows->links() }}
        </div>
    @endif
@endif
</x-filament-panels::page>
