{{-- Skeleton loading placeholder — animated pulse cards --}}
<div class="space-y-5 animate-pulse">

    {{-- 2×2 summary card skeletons --}}
    <div class="grid grid-cols-2 gap-3">
        @foreach(range(1, 4) as $_)
        <div class="bg-gray-100 rounded-2xl p-4 h-24">
            <div class="h-3 bg-gray-200 rounded w-3/4 mb-3"></div>
            <div class="h-6 bg-gray-200 rounded w-1/2 mb-2"></div>
            <div class="h-2 bg-gray-200 rounded w-full"></div>
        </div>
        @endforeach
    </div>

    {{-- Info card skeleton --}}
    <div class="bg-gray-200 rounded-2xl p-4 h-20 flex items-center gap-4">
        <div class="w-14 h-14 rounded-full bg-gray-300 flex-shrink-0"></div>
        <div class="flex-1 space-y-2">
            <div class="h-4 bg-gray-300 rounded w-3/4"></div>
            <div class="h-3 bg-gray-300 rounded w-1/2"></div>
        </div>
    </div>

    {{-- List section skeleton --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100">
            <div class="h-4 bg-gray-200 rounded w-1/3"></div>
        </div>
        @foreach(range(1, 3) as $_)
        <div class="px-4 py-3 border-b border-gray-50 last:border-0">
            <div class="flex items-start justify-between gap-2">
                <div class="flex-1 space-y-2">
                    <div class="h-3 bg-gray-200 rounded w-2/3"></div>
                    <div class="h-2 bg-gray-100 rounded w-1/2"></div>
                </div>
                <div class="h-5 w-14 bg-gray-200 rounded-full flex-shrink-0"></div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Second list section skeleton --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100">
            <div class="h-4 bg-gray-200 rounded w-1/4"></div>
        </div>
        @foreach(range(1, 2) as $_)
        <div class="px-4 py-3 border-b border-gray-50 last:border-0">
            <div class="flex items-start justify-between gap-2">
                <div class="flex-1 space-y-2">
                    <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                    <div class="h-2 bg-gray-100 rounded w-1/3"></div>
                    <div class="h-2 bg-gray-100 rounded w-3/4"></div>
                </div>
                <div class="h-5 w-16 bg-gray-200 rounded-full flex-shrink-0"></div>
            </div>
        </div>
        @endforeach
    </div>

</div>
