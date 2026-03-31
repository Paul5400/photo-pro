<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Dotenv\Dotenv;
use Slim\Factory\AppFactory;

require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$builder = new ContainerBuilder();
$builder->addDefinitions(__DIR__ . '/di/settings.php');
$builder->addDefinitions(__DIR__ . '/di/services.php');
$builder->addDefinitions(__DIR__ . '/di/api.php');
$container = $builder->build();

AppFactory::setContainer($container);
$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

$settings = $container->get('settings') ?? [];
$errorMw = $app->addErrorMiddleware(
    $settings['displayErrorDetails'] ?? true,
    $settings['logError'] ?? true,
    $settings['logErrorDetails'] ?? true
);
$errorMw->getDefaultErrorHandler()->forceContentType('application/json');

$app = (require __DIR__ . '/../src/api/routes.php')($app);

$app->options('/{routes:.+}', function ($request, $response) {
    return $response;
});

return $app;
