<?php

return [
    'secret_key' => env('XENDIT_SECRET_KEY'),
    'callback_token' => env('XENDIT_CALLBACK_TOKEN'),
    'api_url' => env('XENDIT_API_URL', 'https://api.xendit.co'),
    'qr_api_version' => env('XENDIT_QR_API_VERSION', '2022-07-31'),
    'payment_request_api_version' => env('XENDIT_PAYMENT_REQUEST_API_VERSION', '2022-07-31'),

    'default_admin_fee' => (int) env('XENDIT_DEFAULT_ADMIN_FEE', 4000),

    /*
    | Daftar metode pembayaran. Yang tidak aktif di akun Xendit disembunyikan otomatis
    | (sync dari API). Set enabled => false untuk menonaktifkan manual.
    */
    'methods' => [
        'qris' => [
            'label' => 'QRIS',
            'description' => 'Scan QR dengan aplikasi bank atau e-wallet',
            'group' => 'Pembayaran Instan',
            'xendit' => ['QRIS'],
            'admin_fee' => 1500,
        ],
        'dana' => [
            'label' => 'DANA',
            'description' => 'Bayar lewat aplikasi DANA',
            'group' => 'E-Wallet',
            'xendit' => ['DANA'],
            'enabled' => false,
        ],
        'ovo' => [
            'label' => 'OVO',
            'description' => 'Bayar lewat aplikasi OVO',
            'group' => 'E-Wallet',
            'xendit' => ['OVO'],
            'enabled' => false,
        ],
        'shopeepay' => [
            'label' => 'ShopeePay',
            'description' => 'Bayar lewat aplikasi ShopeePay',
            'group' => 'E-Wallet',
            'xendit' => ['SHOPEEPAY'],
            'enabled' => false,
        ],
        'linkaja' => [
            'label' => 'LinkAja',
            'description' => 'Bayar lewat aplikasi LinkAja',
            'group' => 'E-Wallet',
            'xendit' => ['LINKAJA'],
            'enabled' => false,
        ],
        'bca' => [
            'label' => 'BCA',
            'description' => 'Transfer ke Virtual Account BCA',
            'group' => 'Virtual Account',
            'xendit' => ['BCA'],
            'enabled' => false,
            'admin_fee' => 5000,
        ],
        'bni' => [
            'label' => 'BNI',
            'description' => 'Transfer ke Virtual Account BNI',
            'group' => 'Virtual Account',
            'xendit' => ['BNI'],
            'admin_fee' => 5000,
        ],
        'bri' => [
            'label' => 'BRI',
            'description' => 'Transfer ke Virtual Account BRI',
            'group' => 'Virtual Account',
            'xendit' => ['BRI'],
            'admin_fee' => 5000,
        ],
        'mandiri' => [
            'label' => 'Mandiri',
            'description' => 'Transfer ke Virtual Account Mandiri',
            'group' => 'Virtual Account',
            'xendit' => ['MANDIRI'],
            'admin_fee' => 5000,
        ],
        'permata' => [
            'label' => 'Permata',
            'description' => 'Transfer ke Virtual Account Permata',
            'group' => 'Virtual Account',
            'xendit' => ['PERMATA'],
            'admin_fee' => 5000,
        ],
        'cimb' => [
            'label' => 'CIMB Niaga',
            'description' => 'Transfer ke Virtual Account CIMB',
            'group' => 'Virtual Account',
            'xendit' => ['CIMB'],
            'admin_fee' => 5000,
        ],
        'bsi' => [
            'label' => 'BSI',
            'description' => 'Transfer ke Virtual Account BSI',
            'group' => 'Virtual Account',
            'xendit' => ['BSI'],
            'admin_fee' => 5000,
        ],
        'bjb' => [
            'label' => 'BJB',
            'description' => 'Transfer ke Virtual Account BJB',
            'group' => 'Virtual Account',
            'xendit' => ['BJB'],
            'admin_fee' => 5000,
        ],
        'sahabat_sampoerna' => [
            'label' => 'Bank Sahabat Sampoerna',
            'description' => 'Transfer ke Virtual Account BSS',
            'group' => 'Virtual Account',
            'xendit' => ['SAHABAT_SAMPOERNA'],
            'admin_fee' => 5000,
        ],
        'alfamart' => [
            'label' => 'Alfamart',
            'description' => 'Bayar di gerai Alfamart terdekat',
            'group' => 'Gerai Retail',
            'xendit' => ['ALFAMART'],
            'enabled' => false,
        ],
        'indomaret' => [
            'label' => 'Indomaret',
            'description' => 'Bayar di gerai Indomaret terdekat',
            'group' => 'Gerai Retail',
            'xendit' => ['INDOMARET'],
            'admin_fee' => 5500,
        ],
    ],
];
