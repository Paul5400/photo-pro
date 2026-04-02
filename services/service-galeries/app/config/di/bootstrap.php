<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;


$builder = new ContainerBuilder();
$builder->addDefinitions(__DIR__ . '/settings.php');
$builder->addDefinitions(__DIR__ . '/service.php');
$builder->addDefinitions(__DIR__ . '/api.php');
$container = $builder->build();

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addRoutingMiddleware(); 
$app->addBodyParsingMiddleware();   

$settings = $container->get('settings') ?? [];
$errorMw = $app->addErrorMiddleware(
    $settings['displayErrorDetails'] ?? true,
    $settings['logError'] ?? true,
    $settings['logErrorDetails'] ?? true
);
$errorMw->getDefaultErrorHandler()->forceContentType('application/json');


// pre-flight
