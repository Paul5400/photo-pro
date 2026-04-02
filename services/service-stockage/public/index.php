<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

// CORS Headers
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

$app->get('/stockage[/]', function (Request $request, Response $response, $args) {
    $response->getBody()->write(json_encode(['message' => 'Lister les fichiers stockés']));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/stockage[/]', function (Request $request, Response $response, $args) {
    $response->getBody()->write(json_encode(['message' => 'Fichier uploadé avec succès']));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
});

$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Hello from service-stockage API");
    return $response;
});

// Wildcard OPTIONS for CORS preflights
$app->options('/{routes:.+}', function ($request, $response) {
    return $response;
});

$app->run();
