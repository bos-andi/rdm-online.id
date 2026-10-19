<?php

return [
    'base_path' => env('DEPLOY_BASE_PATH', '/var/www/rdm-online'),
    'source_zip' => env('DEPLOY_SOURCE_ZIP', public_path('RDMhosting.zip')),
    'server_ip' => env('DEPLOY_SERVER_IP', '72.62.124.228'),
    'domain' => env('DEPLOY_DOMAIN', 'rdm-online.id'),
    'certbot_email' => env('DEPLOY_CERTBOT_EMAIL', 'admin@rdm-online.id'),
    'deploy_script' => env('DEPLOY_SCRIPT', base_path('scripts/rdm-deploy.sh')),
    'php_fpm_socket' => env('DEPLOY_PHP_FPM_SOCKET', '/run/php/php7.4-fpm.sock'),
    'sql_template' => env('DEPLOY_SQL_TEMPLATE', storage_path('deploy/rdm-base.sql')),
    'suspended_root' => env('DEPLOY_SUSPENDED_ROOT', storage_path('deploy/suspended')),

    'ssh' => [
        'private_key_path' => env('DEPLOY_SSH_PRIVATE_KEY', storage_path('deploy/keys/rdm-deploy')),
        'shared_private_key_path' => env('DEPLOY_SHARED_SSH_KEY'),
        'shared_key_label' => env('DEPLOY_SHARED_SSH_KEY_LABEL', 'apanel'),
    ],

    'remote' => [
        'deploy_script' => env('DEPLOY_REMOTE_SCRIPT', '/opt/rdm-online/scripts/rdm-deploy.sh'),
        'source_zip' => env('DEPLOY_REMOTE_SOURCE_ZIP', '/opt/rdm-online/RDMhosting.zip'),
        'sql_template' => env('DEPLOY_REMOTE_SQL_TEMPLATE', '/opt/rdm-online/storage/deploy/rdm-base.sql'),
        'suspended_root' => env('DEPLOY_REMOTE_SUSPENDED_ROOT', '/opt/rdm-online/storage/deploy/suspended'),
    ],

    'mysql' => [
        'host' => env('DEPLOY_DB_HOST', '127.0.0.1'),
        'admin_user' => env('DEPLOY_DB_ADMIN_USER', 'root'),
        'admin_password' => env('DEPLOY_DB_ADMIN_PASSWORD', ''),
    ],

    'packages' => [
        'monthly' => ['label' => 'Bulanan', 'price' => 15000, 'period' => 'bulan', 'days' => 30],
        'yearly' => ['label' => 'Tahunan', 'price' => 50000, 'period' => 'tahun', 'days' => 365],
        'biennial' => ['label' => '2 Tahunan', 'price' => 200000, 'period' => '2 tahun', 'days' => 730],
    ],

    'pending_payment_ttl_hours' => (int) env('DEPLOY_PENDING_PAYMENT_TTL_HOURS', 24),
    'pending_payment_reminder_minutes' => (int) env('DEPLOY_PENDING_PAYMENT_REMINDER_MINUTES', 5),

    'reserved_subdomains' => [
        'www', 'admin', 'api', 'mail', 'ftp', 'panel', 'dashboard',
        'deploy', 'app', 'cdn', 'static', 'ns1', 'ns2', 'test', 'demo',
    ],

    'steps' => [
        ['key' => 'reserve', 'label' => 'Reservasi subdomain & DNS', 'progress' => 5],
        ['key' => 'extract', 'label' => 'Extract RDMhosting', 'progress' => 25],
        ['key' => 'database', 'label' => 'Buat database MySQL', 'progress' => 35],
        ['key' => 'import', 'label' => 'Import struktur database RDM', 'progress' => 45],
        ['key' => 'config', 'label' => 'Konfigurasi database', 'progress' => 55],
        ['key' => 'nginx', 'label' => 'Konfigurasi Nginx', 'progress' => 70],
        ['key' => 'ssl', 'label' => 'Install SSL (Certbot)', 'progress' => 85],
        ['key' => 'notify', 'label' => 'Kirim notifikasi email', 'progress' => 100],
    ],

    'destroy_steps' => [
        ['key' => 'server', 'label' => 'Hapus file, database, nginx & SSL', 'progress' => 50],
        ['key' => 'dns', 'label' => 'Hapus record DNS subdomain', 'progress' => 80],
        ['key' => 'panel', 'label' => 'Hapus data instance di panel', 'progress' => 100],
    ],
];
