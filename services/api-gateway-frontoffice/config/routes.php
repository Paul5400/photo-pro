<?php

declare(strict_types=1);

use photopro\frontoffice\api\actions\ProxyAction;
use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

return function (App $app) {
    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write("Front Gateway is running!");
        return $response;
    });
    
    // ----- ROUTES FRONTOFFICE (Visiteurs) -----
    // le [/] à la fin d'une route sert à rendre le slash final optionnel
    $app->get('/galeries[/]', ProxyAction::class);
    $app->get('/galeries/{id}[/]', ProxyAction::class);
    $app->post('/galeries/{id}/photos/{photoId}/commentaires[/]', ProxyAction::class);
};
