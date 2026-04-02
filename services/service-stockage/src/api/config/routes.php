<?php

use storage\api\actions\stockage\UploadAction;
use storage\api\middlewares\JwtMiddleware;
use Slim\App;

return function (App $app) {
    $app->post('/upload', UploadAction::class)
        ->add(JwtMiddleware::class);
        
    $app->get('/', function ($request, $response) {
        $response->getBody()->write(json_encode(['message' => 'Service Stockage API is running']));
        return $response->withHeader('Content-Type', 'application/json');
    });
};
