<?php

use Slim\Factory\AppFactory;
use DI\ContainerBuilder;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

// Chargement des variables d'environnement
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Configuration du Container (PHP-DI)
$builder = new ContainerBuilder();
$builder->addDefinitions(__DIR__ . '/../src/api/config/dependencies.php');
$container = $builder->build();

// Création de l'application avec le container
AppFactory::setContainer($container);
$app = AppFactory::create();

$app->addBodyParsingMiddleware();

// Enregistrement des routes
$routes = require __DIR__ . '/../src/api/config/routes.php';
$routes($app);

$app->addRoutingMiddleware();

// CORS Headers
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// Wildcard OPTIONS for CORS preflights
$app->options('/{routes:.+}', function ($request, $response) {
    return $response;
});

$app->run();
