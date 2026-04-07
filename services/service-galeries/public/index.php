<?php
use photopro\galeries\api\actions\galeries\CreateGalerieAction;
use photopro\galeries\api\actions\galeries\AddPhotoGalerieAction;
use photopro\galeries\api\actions\galeries\DeletePhotoFromGalerieAction;
use photopro\galeries\api\actions\galeries\PreviewGalerieAction;
use photopro\galeries\api\actions\galeries\PublishGalerieAction;
use photopro\galeries\api\actions\galeries\UnpublishGalerieAction;
use photopro\galeries\api\middlewares\AuthMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/config/di/bootstrap.php';


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

$app->get('/galeries[/]', function (Request $request, Response $response, $args) {
    $response->getBody()->write(json_encode(['message' => 'Lister les galeries']));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/galeries/{id}[/]', function (Request $request, Response $response, $args) {
    $response->getBody()->write(json_encode(['message' => 'Afficher la galerie ' . $args['id']]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Hello from service-galeries API");
    return $response;
});

$app->post('/galeries', CreateGalerieAction::class)
    ->add(new AuthMiddleware());
$app->patch('/galeries/{id}/photos', AddPhotoGalerieAction::class)
    ->add(new AuthMiddleware());
$app->delete('/galeries/{id}/photos/{photoId}', DeletePhotoFromGalerieAction::class)
    ->add(new AuthMiddleware());
$app->get('/galeries/{id}/preview',PreviewGalerieAction::class )
    ->add(new AuthMiddleware());
$app->post('/galeries/{id}/publish',PublishGalerieAction::class)
    ->add(new AuthMiddleware());
$app->post('/galeries/{id}/unpublish',UnpublishGalerieAction::class)
    ->add(new AuthMiddleware());


$app->run();
