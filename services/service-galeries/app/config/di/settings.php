<?php

return [
    'settings' => [
        'displayErrorDetails' => $_ENV['APP_ENV'] !== 'production',
        'logError' => true,
        'logErrorDetails' => true,
        
        'database' => [
            'driver' => $_ENV['DB_DRIVER'] ?? 'pgsql',
            'host' => $_ENV['DB_HOST'] ?? 'gallery_db',
            'database' => $_ENV['DB_NAME'] ?? 'gallery_db',
            'username' => $_ENV['DB_USER'] ?? 'photo_gallery',
            'password' => $_ENV['DB_PASSWORD'] ?? 'secret',
            'charset' => 'utf8',
        ],
    ],
];
