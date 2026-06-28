<?php

return [

    'single' => [

        'label' => 'Pulihkan',

        'modal' => [

            'heading' => 'Pulihkan :label',

            'actions' => [

                'restore' => [
                    'label' => 'Pulihkan',
                ],

            ],

        ],

        'notifications' => [

            'restored' => [
                'title' => 'Berhasil dipulihkan',
            ],

        ],

    ],

    'multiple' => [

        'label' => 'Pulihkan yang dipilih',

        'modal' => [

            'heading' => 'Pulihkan :label yang dipilih',

            'actions' => [

                'restore' => [
                    'label' => 'Pulihkan',
                ],

            ],

        ],

        'notifications' => [

            'restored' => [
                'title' => 'Berhasil dipulihkan',
            ],

            'restored_partial' => [
                'title' => 'Berhasil memulihkan :count dari :total',
                'missing_authorization_failure_message' => 'Anda tidak memiliki izin untuk memulihkan :count.',
                'missing_processing_failure_message' => ':count tidak dapat dipulihkan.',
            ],

            'restored_none' => [
                'title' => 'Gagal memulihkan',
                'missing_authorization_failure_message' => 'Anda tidak memiliki izin untuk memulihkan :count.',
                'missing_processing_failure_message' => ':count tidak dapat dipulihkan.',
            ],

        ],

    ],

];
