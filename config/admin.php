<?php

return [
    'email' => env('ADMIN_EMAIL'),
    'password' => env('ADMIN_PASSWORD'),

    'vps' => [
        'max_rdm_capacity' => (int) env('ADMIN_VPS_MAX_RDM', 150),
        'servers' => [
            [
                'code' => 'ID-01',
                'location' => 'Jakarta',
                'ip' => env('DEPLOY_SERVER_IP', '72.62.124.228'),
            ],
        ],
    ],
];
