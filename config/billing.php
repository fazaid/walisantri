<?php

return [
    'diskon_tahunan' => [
        'aktif'       => true,
        'bonus_bulan' => 2,
        'label'       => 'Hemat 2 bulan (bayar 10, dapat 12)',
    ],

    'bank_transfer' => [
        [
            'bank'      => env('BILLING_BANK1_NAMA', 'BCA'),
            'nomor'     => env('BILLING_BANK1_NOMOR', '1234567890'),
            'atas_nama' => env('BILLING_BANK1_ATAS_NAMA', 'PT Walisantri Digital'),
        ],
        [
            'bank'      => env('BILLING_BANK2_NAMA', 'Mandiri'),
            'nomor'     => env('BILLING_BANK2_NOMOR', '1110002223334'),
            'atas_nama' => env('BILLING_BANK2_ATAS_NAMA', 'PT Walisantri Digital'),
        ],
    ],

    'order_expiry_hours'   => 24,
    'nomor_order_prefix'   => 'WS',
    'nomor_invoice_prefix' => 'INV',
];
