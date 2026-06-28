<?php

return [

    'column_manager' => [

        'heading' => 'Kolom',

        'actions' => [

            'apply' => [
                'label' => 'Terapkan kolom',
            ],

            'reset' => [
                'label' => 'Reset',
            ],

        ],

    ],

    'columns' => [

        'actions' => [
            'label' => 'Aksi',
        ],

        'select' => [

            'loading_message' => 'Memuat...',

            'no_options_message' => 'Tidak ada pilihan tersedia.',

            'no_search_results_message' => 'Tidak ada pilihan yang cocok.',

            'placeholder' => 'Pilih salah satu',

            'searching_message' => 'Mencari...',

            'search_prompt' => 'Ketik untuk mencari...',

        ],

        'text' => [

            'actions' => [
                'collapse_list' => 'Tampilkan :count lebih sedikit',
                'expand_list' => 'Tampilkan :count lagi',
            ],

            'more_list_items' => 'dan :count lainnya',

        ],

    ],

    'fields' => [

        'bulk_select_page' => [
            'label' => 'Pilih/batal pilih semua untuk aksi massal.',
        ],

        'bulk_select_record' => [
            'label' => 'Pilih/batal pilih item :key untuk aksi massal.',
        ],

        'bulk_select_group' => [
            'label' => 'Pilih/batal pilih grup :title untuk aksi massal.',
        ],

        'search' => [
            'label' => 'Cari',
            'placeholder' => 'Cari',
            'indicator' => 'Pencarian',
        ],

    ],

    'summary' => [

        'heading' => 'Ringkasan',

        'subheadings' => [
            'all' => 'Semua :label',
            'group' => 'Ringkasan :group',
            'page' => 'Halaman ini',
        ],

        'summarizers' => [

            'average' => [
                'label' => 'Rata-rata',
            ],

            'count' => [
                'label' => 'Jumlah',
            ],

            'sum' => [
                'label' => 'Total',
            ],

        ],

    ],

    'actions' => [

        'disable_reordering' => [
            'label' => 'Selesai mengurutkan',
        ],

        'enable_reordering' => [
            'label' => 'Urutkan ulang',
        ],

        'filter' => [
            'label' => 'Filter',
        ],

        'group' => [
            'label' => 'Kelompokkan',
        ],

        'open_bulk_actions' => [
            'label' => 'Aksi massal',
        ],

        'column_manager' => [
            'label' => 'Pengaturan kolom',
        ],

    ],

    'empty' => [

        'heading' => 'Tidak ada :model',

        'description' => 'Tambahkan :model untuk memulai.',

    ],

    'filters' => [

        'actions' => [

            'apply' => [
                'label' => 'Terapkan filter',
            ],

            'remove' => [
                'label' => 'Hapus filter',
            ],

            'remove_all' => [
                'label' => 'Hapus semua filter',
                'tooltip' => 'Hapus semua filter',
            ],

            'reset' => [
                'label' => 'Reset',
            ],

        ],

        'heading' => 'Filter',

        'indicator' => 'Filter aktif',

        'multi_select' => [
            'placeholder' => 'Semua',
        ],

        'select' => [

            'placeholder' => 'Semua',

            'relationship' => [
                'empty_option_label' => 'Tidak ada',
            ],

        ],

        'trashed' => [

            'label' => 'Data terhapus',

            'only_trashed' => 'Hanya data terhapus',

            'with_trashed' => 'Termasuk data terhapus',

            'without_trashed' => 'Tanpa data terhapus',

        ],

    ],

    'grouping' => [

        'fields' => [

            'group' => [
                'label' => 'Kelompokkan berdasarkan',
            ],

            'direction' => [

                'label' => 'Arah pengelompokan',

                'options' => [
                    'asc' => 'Naik',
                    'desc' => 'Turun',
                ],

            ],

        ],

    ],

    'reorder_indicator' => 'Seret dan lepas untuk mengurutkan.',

    'selection_indicator' => [

        'selected_count' => '1 data dipilih|:count data dipilih',

        'actions' => [

            'select_all' => [
                'label' => 'Pilih semua :count',
            ],

            'deselect_all' => [
                'label' => 'Batal pilih semua',
            ],

        ],

    ],

    'sorting' => [

        'fields' => [

            'column' => [
                'label' => 'Urutkan berdasarkan',
            ],

            'direction' => [

                'label' => 'Arah pengurutan',

                'options' => [
                    'asc' => 'Naik',
                    'desc' => 'Turun',
                ],

            ],

        ],

    ],

    'default_model_label' => 'data',

];
