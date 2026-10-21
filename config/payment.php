<?php

$gateway = env('PAYMENT_GATEWAY', 'andidev');

return [
    /*
    | Gateway untuk transaksi baru: "andidev", "tripay", atau "xendit".
    | Transaksi lama tetap disinkronkan lewat gateway tempat transaksi itu dibuat.
    */
    'gateway' => $gateway,
    'label' => match ($gateway) {
        'andidev' => 'Andidev Pay',
        'tripay' => 'Tripay',
        default => 'Xendit',
    },
];
