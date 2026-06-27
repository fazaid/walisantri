{{-- File: resources/views/filament/admin/bottom-nav.blade.php --}}
@php
    $role = auth()->user()?->role;
@endphp

@if(in_array($role, [\App\Enums\UserRole::AdminPesantren->value, \App\Enums\UserRole::Ustadz->value]))
    @php
        $tabs = [
            [
                'label'  => 'Dashboard',
                'url'    => route('filament.admin.pages.dashboard'),
                'active' => request()->routeIs('filament.admin.pages.dashboard'),
                'icon'   => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
            ],
        ];

        if (\App\Filament\Clusters\Santri::canAccessClusteredComponents()) {
            $tabs[] = [
                'label'  => 'Santri',
                'url'    => \App\Filament\Clusters\Santri::getUrl(),
                'active' => request()->routeIs('filament.admin.santri*'),
                'icon'   => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-3.13a4 4 0 100-8 4 4 0 000 8zm6 3c0-1.657-3.582-3-8-3s-8 1.343-8 3',
            ];
        }

        if (\App\Filament\Clusters\Tahfidz::canAccessClusteredComponents()) {
            $tabs[] = [
                'label'  => 'Tahfidz',
                'url'    => \App\Filament\Clusters\Tahfidz::getUrl(),
                'active' => request()->routeIs('filament.admin.tahfidz*'),
                'icon'   => 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25',
            ];
        }

        if (\App\Filament\Clusters\Mutabaah::canAccessClusteredComponents()) {
            $tabs[] = [
                'label'  => 'Mutabaah',
                'url'    => \App\Filament\Clusters\Mutabaah::getUrl(),
                'active' => request()->routeIs('filament.admin.mutabaah*'),
                'icon'   => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            ];
        }

        if (\App\Filament\Clusters\Kesantrian::canAccessClusteredComponents()) {
            $tabs[] = [
                'label'  => 'Kesantrian',
                'url'    => \App\Filament\Clusters\Kesantrian::getUrl(),
                'active' => request()->routeIs('filament.admin.kesantrian*'),
                'icon'   => 'M12 2.714l6.825 2.731c.6.24 1.05.84 1.05 1.514 0 5.439-2.748 10.575-7.875 12.792-5.127-2.217-7.875-7.353-7.875-12.792 0-.674.45-1.274 1.05-1.514L12 2.714zM9 12.75L11.25 15 15 9.75',
            ];
        }

        if (\App\Filament\Clusters\Akademik::canAccessClusteredComponents()) {
            $tabs[] = [
                'label'  => 'Akademik',
                'url'    => \App\Filament\Clusters\Akademik::getUrl(),
                'active' => request()->routeIs('filament.admin.akademik*'),
                'icon'   => 'M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222',
            ];
        }
    @endphp

    <nav
        x-data
        x-show="! $store.sidebar.isOpen"
        x-effect="document.body.classList.toggle('pb-14', ! $store.sidebar.isOpen)"
        style="display: none"
        x-transition
        class="fixed bottom-0 inset-x-0 z-40 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 shadow-lg"
    >
        <div class="flex items-stretch overflow-x-auto">
            @foreach($tabs as $tab)
                <a
                    href="{{ $tab['url'] }}"
                    class="flex-1 min-w-[64px] flex flex-col items-center justify-center gap-0.5 py-2 text-[11px] font-medium transition-colors
                        {{ $tab['active'] ? 'text-teal-600 dark:text-teal-400' : 'text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300' }}"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="{{ $tab['active'] ? 2 : 1.6 }}" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $tab['icon'] }}" />
                    </svg>
                    {{ $tab['label'] }}
                </a>
            @endforeach
        </div>
    </nav>
@endif
