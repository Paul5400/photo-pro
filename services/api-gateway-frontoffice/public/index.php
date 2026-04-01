<?php

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use photopro\frontoffice\api\middleware\CorsMiddleware;

require __DIR__ . '/../vendor/autoload.php';

// 1. Instancier le conteneur DI
$containerBuilder = new ContainerBuilder();
$dependencies = require __DIR__ . '/../config/dependencies.php';
$dependencies($containerBuilder);

$container = $containerBuilder->build();

// 2. Assigner le conteneur à l'application Slim
AppFactory::setContainer($container);
$app = AppFactory::create();


// 3. Middlewares globaux (Parsing du body, Routing, Error handling)
$app->add(new CorsMiddleware());
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

// 4. Enregistrement des routes
$routes = require __DIR__ . '/../config/routes.php';
$routes($app);

// 5. Exécution
$app->run();
