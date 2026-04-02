<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use photopro\backoffice\api\middleware\CorsMiddleware;

require __DIR__ . '/../vendor/autoload.php';

// 1. Initialiser le conteneur PHP-DI
$containerBuilder = new ContainerBuilder();
$dependencies = require __DIR__ . '/../config/dependencies.php';
$dependencies($containerBuilder);

$container = $containerBuilder->build();

// 2. Créer l'application Slim avec le conteneur
AppFactory::setContainer($container);
$app = AppFactory::create();

// 3. Middlewares globaux
$app->add(new CorsMiddleware());
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

// Error Middleware
$app->addErrorMiddleware(true, true, true);

// 4. Enregistrer les routes
$routes = require __DIR__ . '/../config/routes.php';
$routes($app);

// 5. Lancer l'application
$app->run();
