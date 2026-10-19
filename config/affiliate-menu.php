<?php

return [
    'brand' => [
        'subtitle' => 'Panel Affiliate',
    ],

    'items' => [
        ['icon' => 'home', 'label' => 'Dashboard', 'route' => 'affiliate.dashboard'],
        ['icon' => 'users', 'label' => 'Referral Saya', 'route' => 'affiliate.referrals'],
        ['icon' => 'coins', 'label' => 'Komisi', 'route' => 'affiliate.commissions'],
        ['icon' => 'wallet', 'label' => 'Pencairan', 'route' => 'affiliate.payouts'],
        ['icon' => 'user', 'label' => 'Profil & Rekening', 'route' => 'affiliate.profile'],
    ],
];
