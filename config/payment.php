<?php

$gateway = env('PAYMENT_GATEWAY', 'xendit');

return [
    /*
    | Gateway untuk transaksi baru: "tripay" atau "xendit".
    | Transaksi lama tetap disinkronkan lewat gateway tempat transaksi itu dibuat.
    */
    'gateway' => $gateway,
    'label' => $gateway === 'tripay' ? 'Tripay' : 'Xendit',
];
