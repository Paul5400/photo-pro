<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use GuzzleHttp\Client;
use photopro\backoffice\api\actions\ProxyAction;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        // --- CONFIGURATION GATEWAY ---
        'gateway.mock_mode' => (getenv('MOCK_MODE') === 'true'), 
        
        // --- SERVICES / ACTIONS ---
        ProxyAction::class => function (ContainerInterface $c) {
            return new ProxyAction($c, $c->get('gateway.mock_mode'));
        },

        // Client HTTP Guzzle pour le service Galerie
        'gallery.client' => function (ContainerInterface $c) {
            return new Client([
                'base_uri' => getenv('GALLERY_URL'),
                'timeout' => 5.0,
            ]);
        },
        
        // Client HTTP Guzzle pour le service d'Authentification
        'auth.client' => function (ContainerInterface $c) {
            return new Client([
                'base_uri' => getenv('AUTH_URL'),
                'timeout' => 5.0,
            ]);
        },

        // Client HTTP Guzzle pour le service de Stockage
        'stockage.client' => function (ContainerInterface $c) {
            return new Client([
                'base_uri' => getenv('STOCKAGE_URL'),
                'timeout' => 5.0,
            ]);
        },
    ]);
};
