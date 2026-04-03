<?php

return [
    'settings' => [
        'logError' => true,
        'logErrorDetails' => true,
        
        'database' => [
            'driver' =>  'pgsql',
            'host' => 'gallery.db',
            'database' =>  'gallery_db',
            'username' =>  'photo_gallery',
            'password' => 'secret',
            'charset' => 'utf8',
        ],
    ],

    'amqp' => [
        'host'     => getenv('RABBITMQ_HOST')  ?: 'rabbitmq',
        'port'     => (int)(getenv('RABBITMQ_PORT') ?: 5672),
        'user'     => getenv('RABBITMQ_USER')  ?: 'photopro',
        'pass'     => getenv('RABBITMQ_PASS')  ?: 'photopro',
        'vhost'    => getenv('RABBITMQ_VHOST') ?: '/',
        'exchange' => getenv('AMQP_EXCHANGE')  ?: 'photopro.events',
    ],
];
