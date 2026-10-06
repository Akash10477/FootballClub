<?php
/**
 * FC Scorpio — application configuration.
 * Edit the database credentials below to match your local setup (XAMPP defaults shown).
 */
return [
    'app' => [
        'name'     => 'FC Scorpio',
        'tagline'  => 'Where passion meets the pitch',
        'timezone' => 'Asia/Dhaka',
        'currency' => '৳',
        // Leave empty to auto-detect (works for http://localhost/football_club/).
        // Set it manually if the site runs behind a proxy, e.g. '/football_club'.
        'base_url' => '',
        'debug'    => true,   // set to false in production
    ],
    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'name'     => 'football_db',
        'user'     => 'root',
        'password' => '',
    ],
    'uploads' => [
        'max_bytes' => 2 * 1024 * 1024,                 // 2 MB
        'mimes'     => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'],
    ],
];
