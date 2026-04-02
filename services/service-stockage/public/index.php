<?php
<<<<<<< HEAD

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
=======
declare(strict_types=1);

use DI\ContainerBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Factory\AppFactory;
use Slim\Interfaces\ErrorRendererInterface;

use function DI\autowire;

require __DIR__ . '/../vendor/autoload.php';

if (file_exists(__DIR__ . '/../.env')) {
    Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();
}

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(require __DIR__ . '/../config/settings.php');
$containerBuilder->addDefinitions(require __DIR__ . '/../config/di/services.php');
$containerBuilder->addDefinitions([
    photopro\stockage\api\actions\stockage\UploadAction::class => autowire(),
]);
$container = $containerBuilder->build();

$app = AppFactory::createFromContainer($container);

$app->addBodyParsingMiddleware();

$app->get('/', static function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
    $response->getBody()->write(json_encode(['service' => 'service-stockage', 'status' => 'ok'], JSON_UNESCAPED_SLASHES));
    return $response->withHeader('Content-Type', 'application/json');
});

(require __DIR__ . '/../src/api/routes.php')($app);

$errorMiddleware = $app->addErrorMiddleware((bool) ($_ENV['APP_DEBUG'] ?? true), true, true);
$errorMiddleware->getDefaultErrorHandler()->registerErrorRenderer('application/json', new class implements ErrorRendererInterface {
    public function __invoke(\Throwable $exception, bool $displayErrorDetails): string
    {
        $payload = [
            'error' => [
                'type' => (new \ReflectionClass($exception))->getShortName(),
                'message' => $exception->getMessage(),
            ],
        ];

        if ($displayErrorDetails) {
            $payload['error']['trace'] = explode("\n", $exception->getTraceAsString());
        }

        return json_encode($payload, JSON_UNESCAPED_SLASHES);
    }
});

$app->options('/{routes:.+}', static function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
    return $response;
});
>>>>>>> origin/feat/StockageS3

$app->run();
