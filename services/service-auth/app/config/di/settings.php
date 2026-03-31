<?php

return [
    'settings' => [
        'displayErrorDetails' => ($_ENV['APP_ENV'] ?? 'dev') !== 'production',
        'logError' => true,
        'logErrorDetails' => true,

        'database' => [
            'driver'   => $_ENV['DB_DRIVER']   ?? 'pgsql',
            'host'     => $_ENV['DB_HOST']     ?? 'auth.db',
            'database' => $_ENV['DB_NAME']     ?? 'auth_db',
            'username' => $_ENV['DB_USER']     ?? 'photo_auth',
            'password' => $_ENV['DB_PASSWORD'] ?? 'secret',
        ],

        'jwt' => [
            'secret' => $_ENV['JWT_SECRET'] ?? '',
        ],
    ],
];
