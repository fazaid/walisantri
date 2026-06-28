<?php

return [

    'single' => [

        'label' => 'Hapus permanen',

        'modal' => [

            'heading' => 'Hapus permanen :label',

            'actions' => [

                'delete' => [
                    'label' => 'Hapus',
                ],

            ],

        ],

        'notifications' => [

            'deleted' => [
                'title' => 'Berhasil dihapus permanen',
            ],

        ],

    ],

    'multiple' => [

        'label' => 'Hapus permanen yang dipilih',

        'modal' => [

            'heading' => 'Hapus permanen :label yang dipilih',

            'actions' => [

                'delete' => [
                    'label' => 'Hapus',
                ],

            ],

        ],

        'notifications' => [

            'deleted' => [
                'title' => 'Berhasil dihapus permanen',
            ],

            'deleted_partial' => [
                'title' => 'Berhasil menghapus permanen :count dari :total',
                'missing_authorization_failure_message' => 'Anda tidak memiliki izin untuk menghapus :count.',
                'missing_processing_failure_message' => ':count tidak dapat dihapus.',
            ],

            'deleted_none' => [
                'title' => 'Gagal menghapus permanen',
                'missing_authorization_failure_message' => 'Anda tidak memiliki izin untuk menghapus :count.',
                'missing_processing_failure_message' => ':count tidak dapat dihapus.',
            ],

        ],

    ],

];
