<?php

return [
    'url' => rtrim((string) env('ANDIDEV_PAY_URL', 'https://payment.andidev.id'), '/'),
    'key' => env('ANDIDEV_PAY_KEY'),
    'webhook_secret' => env('ANDIDEV_PAY_WEBHOOK_SECRET'),
    'expiry_minutes' => (int) env('ANDIDEV_PAY_EXPIRY_MINUTES', 1440),

    /*
    | Metode yang ditawarkan di checkout RDM. Key "andidev" adalah kode channel
    | Andidev Pay (QRIS, VA_*, ALFAMART, INDOMARET). Biaya di bawah adalah
    | cadangan; nilai aktif diambil dari GET /api/v1/channels bila API tersedia.
    | fee_bearer "merchant" berarti pelanggan tidak ditambah biaya admin.
    */
    'methods' => [
        'qris' => [
            'label' => 'QRIS',
            'description' => 'Scan QR dengan aplikasi bank atau e-wallet',
            'group' => 'Pembayaran Instan',
            'andidev' => 'QRIS',
            'fee_percent' => 0.7,
            'fee_flat' => 300,
            'fee_bearer' => 'merchant',
            'expiry_minutes' => 60,
        ],
        'bca' => [
            'label' => 'BCA',
            'description' => 'Transfer ke Virtual Account BCA',
            'group' => 'Virtual Account',
            'andidev' => 'VA_BCA',
            'fee_percent' => 0,
            'fee_flat' => 5000,
            'fee_bearer' => 'merchant',
        ],
        'bni' => [
            'label' => 'BNI',
            'description' => 'Transfer ke Virtual Account BNI',
            'group' => 'Virtual Account',
            'andidev' => 'VA_BNI',
            'fee_percent' => 0,
            'fee_flat' => 5000,
            'fee_bearer' => 'merchant',
        ],
        'bri' => [
            'label' => 'BRI',
            'description' => 'Transfer ke Virtual Account BRI',
            'group' => 'Virtual Account',
            'andidev' => 'VA_BRI',
            'fee_percent' => 0,
            'fee_flat' => 5000,
            'fee_bearer' => 'merchant',
        ],
        'mandiri' => [
            'label' => 'Mandiri',
            'description' => 'Transfer ke Virtual Account Mandiri',
            'group' => 'Virtual Account',
            'andidev' => 'VA_MANDIRI',
            'fee_percent' => 0,
            'fee_flat' => 5000,
            'fee_bearer' => 'merchant',
        ],
        'permata' => [
            'label' => 'Permata',
            'description' => 'Transfer ke Virtual Account Permata',
            'group' => 'Virtual Account',
            'andidev' => 'VA_PERMATA',
            'fee_percent' => 0,
            'fee_flat' => 5000,
            'fee_bearer' => 'merchant',
        ],
        'cimb' => [
            'label' => 'CIMB Niaga',
            'description' => 'Transfer ke Virtual Account CIMB Niaga',
            'group' => 'Virtual Account',
            'andidev' => 'VA_CIMB',
            'fee_percent' => 0,
            'fee_flat' => 5000,
            'fee_bearer' => 'merchant',
        ],
        'bsi' => [
            'label' => 'BSI',
            'description' => 'Transfer ke Virtual Account BSI',
            'group' => 'Virtual Account',
            'andidev' => 'VA_BSI',
            'fee_percent' => 0,
            'fee_flat' => 5000,
            'fee_bearer' => 'merchant',
        ],
        'alfamart' => [
            'label' => 'Alfamart',
            'description' => 'Bayar di kasir Alfamart',
            'group' => 'Gerai Retail',
            'andidev' => 'ALFAMART',
            'fee_percent' => 0,
            'fee_flat' => 7000,
            'fee_bearer' => 'merchant',
        ],
        'indomaret' => [
            'label' => 'Indomaret',
            'description' => 'Bayar di kasir Indomaret',
            'group' => 'Gerai Retail',
            'andidev' => 'INDOMARET',
            'fee_percent' => 0,
            'fee_flat' => 7000,
            'fee_bearer' => 'merchant',
        ],
    ],
];
