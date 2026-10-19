<?php

return [
    'mode' => env('TRIPAY_MODE', 'sandbox'),
    'merchant_code' => env('TRIPAY_MERCHANT_CODE'),
    'api_key' => env('TRIPAY_API_KEY'),
    'private_key' => env('TRIPAY_PRIVATE_KEY'),
    'api_url' => env('TRIPAY_API_URL'),

    'default_admin_fee' => (int) env('TRIPAY_DEFAULT_ADMIN_FEE', 5000),
    'expiry_minutes' => (int) env('TRIPAY_EXPIRY_MINUTES', 1440),

    /*
    | Daftar metode pembayaran. Key "tripay" berisi kode channel Tripay (urutan prioritas);
    | yang tidak aktif di merchant Tripay disembunyikan otomatis. "expiry_minutes" harus
    | berada di dalam batas min/max expired channel (lihat dokumentasi Tripay).
    */
    'methods' => [
        'qris' => [
            'label' => 'QRIS',
            'description' => 'Scan QR dengan aplikasi bank atau e-wallet',
            'group' => 'Pembayaran Instan',
            'tripay' => ['QRIS2', 'QRISC', 'QRIS'],
            'admin_fee' => 0,
            'expiry_minutes' => 60,
        ],
        'dana' => [
            'label' => 'DANA',
            'description' => 'Bayar lewat aplikasi DANA',
            'group' => 'E-Wallet',
            'tripay' => ['DANA'],
            'enabled' => false,
            'expiry_minutes' => 60,
        ],
        'ovo' => [
            'label' => 'OVO',
            'description' => 'Bayar lewat aplikasi OVO',
            'group' => 'E-Wallet',
            'tripay' => ['OVO'],
            'enabled' => false,
        ],
        'shopeepay' => [
            'label' => 'ShopeePay',
            'description' => 'Bayar lewat aplikasi ShopeePay',
            'group' => 'E-Wallet',
            'tripay' => ['SHOPEEPAY'],
            'enabled' => false,
            'expiry_minutes' => 60,
        ],
        'bca' => [
            'label' => 'BCA',
            'description' => 'Transfer ke Virtual Account BCA',
            'group' => 'Virtual Account',
            'tripay' => ['BCAVA'],
            'admin_fee' => 6000,
        ],
        'bni' => [
            'label' => 'BNI',
            'description' => 'Transfer ke Virtual Account BNI',
            'group' => 'Virtual Account',
            'tripay' => ['BNIVA'],
            'admin_fee' => 5000,
        ],
        'bri' => [
            'label' => 'BRI',
            'description' => 'Transfer ke Virtual Account BRI',
            'group' => 'Virtual Account',
            'tripay' => ['BRIVA'],
            'admin_fee' => 5000,
        ],
        'mandiri' => [
            'label' => 'Mandiri',
            'description' => 'Transfer ke Virtual Account Mandiri',
            'group' => 'Virtual Account',
            'tripay' => ['MANDIRIVA'],
            'admin_fee' => 5000,
        ],
        'permata' => [
            'label' => 'Permata',
            'description' => 'Transfer ke Virtual Account Permata',
            'group' => 'Virtual Account',
            'tripay' => ['PERMATAVA'],
            'admin_fee' => 5000,
        ],
        'cimb' => [
            'label' => 'CIMB Niaga',
            'description' => 'Transfer ke Virtual Account CIMB',
            'group' => 'Virtual Account',
            'tripay' => ['CIMBVA'],
            'admin_fee' => 5000,
        ],
        'bsi' => [
            'label' => 'BSI',
            'description' => 'Transfer ke Virtual Account BSI',
            'group' => 'Virtual Account',
            'tripay' => ['BSIVA'],
            'admin_fee' => 5000,
            'expiry_minutes' => 180,
        ],
        'muamalat' => [
            'label' => 'Muamalat',
            'description' => 'Transfer ke Virtual Account Muamalat',
            'group' => 'Virtual Account',
            'tripay' => ['MUAMALATVA'],
            'admin_fee' => 5000,
            'expiry_minutes' => 180,
        ],
        'danamon' => [
            'label' => 'Danamon',
            'description' => 'Transfer ke Virtual Account Danamon',
            'group' => 'Virtual Account',
            'tripay' => ['DANAMONVA'],
            'admin_fee' => 5000,
        ],
        'ocbc' => [
            'label' => 'OCBC NISP',
            'description' => 'Transfer ke Virtual Account OCBC NISP',
            'group' => 'Virtual Account',
            'tripay' => ['OCBCVA'],
            'admin_fee' => 5000,
        ],
        'sahabat_sampoerna' => [
            'label' => 'Bank Sahabat Sampoerna',
            'description' => 'Transfer ke Virtual Account BSS',
            'group' => 'Virtual Account',
            'tripay' => ['SAMPOERNAVA'],
            'admin_fee' => 5000,
        ],
        'other_bank' => [
            'label' => 'Bank Lain',
            'description' => 'Transfer dari bank lain via Virtual Account',
            'group' => 'Virtual Account',
            'tripay' => ['OTHERBANKVA'],
            'admin_fee' => 5000,
        ],
        'alfamart' => [
            'label' => 'Alfamart',
            'description' => 'Bayar di gerai Alfamart terdekat',
            'group' => 'Gerai Retail',
            'tripay' => ['ALFAMART'],
            'admin_fee' => 4000,
        ],
        'alfamidi' => [
            'label' => 'Alfamidi',
            'description' => 'Bayar di gerai Alfamidi terdekat',
            'group' => 'Gerai Retail',
            'tripay' => ['ALFAMIDI'],
            'admin_fee' => 4000,
        ],
        'indomaret' => [
            'label' => 'Indomaret',
            'description' => 'Bayar di gerai Indomaret terdekat',
            'group' => 'Gerai Retail',
            'tripay' => ['INDOMARET'],
            'admin_fee' => 4000,
        ],
    ],
];
