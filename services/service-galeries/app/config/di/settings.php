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
];
