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

// Chargement des routes
$routes = require __DIR__ . '/../src/api/config/routes.php';
$routes($app);

// Middlewares standards
$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(true, true, true);

$app->run();
