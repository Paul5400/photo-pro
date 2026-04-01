<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use Dotenv\Dotenv;
use Slim\Factory\AppFactory;
use alt\api\middlewares\CorsMiddleware;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$builder = new ContainerBuilder();
$builder->addDefinitions(__DIR__ . '/settings.php');
$builder->addDefinitions(__DIR__ . '/services.php');
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

$app = (require __DIR__ . '/../src/api/routes.php')($app);

// $app->add(new CorsMiddleware());

// pre-flight
$app->options('/{routes:.+}', function ($request, $response) {
    return $response;
});

return $app;