<?php
return [
    'site_name' => 'CDEM Solutions',
    'site_url'  => 'https://cdemsolutions.com',
    'contact_email' => 'hello@cdemsolutions.com',

    'db_path' => __DIR__ . '/../storage/cdem.db',

    'smtp' => [
        'host'      => '',
        'port'      => 587,
        'user'      => '',
        'pass'      => '',
        'from'      => 'noreply@cdemsolutions.com',
        'from_name' => 'CDEM Solutions',
    ],

    'admin' => [
        'session_lifetime' => 3600,
        'max_login_attempts_ip' => 10,
        'max_login_attempts_user' => 5,
        'lockout_duration' => 900, // 15 minutes
    ],
];
