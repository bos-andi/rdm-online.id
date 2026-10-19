<?php

return [
    'brand' => [
        'title' => 'RDM-ONLINE.ID',
        'subtitle' => 'Panel Pengguna',
    ],

    'items' => [
        ['icon' => 'home', 'label' => 'Dashboard', 'route' => 'user.dashboard'],
        ['icon' => 'book-open', 'label' => 'RDM Saya', 'route' => 'user.rdm'],
        ['icon' => 'plus-circle', 'label' => 'Buat RDM Baru', 'route' => 'user.deploy'],
        ['icon' => 'globe', 'label' => 'Domain & DNS', 'route' => 'user.domain'],
        ['icon' => 'hard-drive', 'label' => 'Backup & Restore', 'route' => 'user.backup'],
        ['icon' => 'activity', 'label' => 'Monitoring', 'route' => 'user.monitoring'],
        ['icon' => 'credit-card', 'label' => 'Billing & Pembayaran', 'route' => 'user.billing'],
        ['icon' => 'life-buoy', 'label' => 'Support Center', 'route' => 'user.support'],
        ['icon' => 'user', 'label' => 'Profil Akun', 'route' => 'user.profile'],
    ],
];
