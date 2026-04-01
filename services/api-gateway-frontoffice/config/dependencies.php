<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use GuzzleHttp\Client;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        // Client HTTP Guzzle pour le service Galerie
        'gallery.client' => function (ContainerInterface $c) {
            return new Client([
                'base_uri' => 'http://api.gallery:80',
                'timeout' => 5.0,
            ]);
        },
        
        // Client HTTP Guzzle pour le service d'Authentification
        // 'auth.client' => function (ContainerInterface $c) {
        //     return new Client([
        //         'base_uri' => 'http://api.auth:80',
        //         'timeout' => 5.0,
        //     ]);
        // },

    ]);
};
