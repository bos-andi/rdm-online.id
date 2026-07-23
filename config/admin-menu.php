<?php

return [
    'brand' => [
        'title' => 'RDM Admin',
        'subtitle' => 'Panel Administrator',
    ],

    'items' => [
        ['icon' => 'layout-dashboard', 'label' => 'Dashboard', 'route' => 'admin.dashboard'],
        ['icon' => 'users', 'label' => 'Manajemen Pelanggan', 'route' => 'admin.pelanggan'],
        ['icon' => 'server', 'label' => 'Manajemen RDM', 'route' => 'admin.rdm'],
        ['icon' => 'monitor', 'label' => 'Server VPS', 'route' => 'admin.vps'],
        ['icon' => 'globe', 'label' => 'DNS & Cloudflare', 'route' => 'admin.dns'],
        ['icon' => 'database', 'label' => 'Database Manager', 'route' => 'admin.database'],
        ['icon' => 'hard-drive', 'label' => 'Backup Center', 'route' => 'admin.backup'],
        ['icon' => 'credit-card', 'label' => 'Billing & Pembayaran', 'route' => 'admin.billing'],
        ['icon' => 'life-buoy', 'label' => 'Support Management', 'route' => 'admin.support'],
        ['icon' => 'megaphone', 'label' => 'Pengumuman', 'route' => 'admin.pengumuman'],
        ['icon' => 'settings', 'label' => 'Pengaturan Sistem', 'route' => 'admin.settings'],
        ['icon' => 'file-text', 'label' => 'Log Aktivitas', 'route' => 'admin.log'],
    ],
];
