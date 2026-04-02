<?php
use photopro\galeries\api\actions\galeries\CreateGalerieAction;
use photopro\galeries\api\actions\galeries\AddPhotoGalerieAction;
use photopro\galeries\api\actions\galeries\DeletePhotoFromGalerieAction;
use photopro\galeries\app\api\middlewares\AuthMiddleware;
use photopro\src\api\actions\galeries\PreviewGalerieAction;
use photopro\src\api\actions\galeries\PublishGalerieAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/config/di/bootstrap.php';


$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Hello from service-galeries API");
    return $response;
});
$app->post('/galeries', CreateGalerieAction::class);
// ->add(new AuthMiddleware());
$app->patch('/galeries/{id}/photos', AddPhotoGalerieAction::class);
// ->add(new AuthMiddleware());
$app->delete('/galeries/{id}/photos/{photoId}', DeletePhotoFromGalerieAction::class);
// ->add(new AuthMiddleware());
$app->get('/galeries/{id}/preview',PreviewGalerieAction::class );
$app->post('/galeries/{id}/publish',PublishGalerieAction::class);
$app->post('/galeries/{id}/unpublish',PublishGalerieAction::class);

$app->run();
