<?php

declare(strict_types=1);

use photopro\backoffice\api\actions\ProxyAction;
use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use photopro\backoffice\api\middleware\JwtAuthMiddleware;

return function (App $app) {
    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write("Backoffice Gateway is running!");
        return $response;
    });
    
    // ----- ROUTES AUTHENTIFICATION (Publiques) -----
    $app->post('/auth/register[/]', ProxyAction::class);
    $app->post('/auth/signin[/]', ProxyAction::class);
    $app->post('/auth/refresh[/]', ProxyAction::class);

    // ----- ROUTES PROTEGEES (JWT Requis) -----
    $app->group('', function ($group) {
        // Galeries
        $group->get('/galeries[/]', ProxyAction::class);
        $group->post('/galeries[/]', ProxyAction::class);
        $group->patch('/galeries/{id}[/]', ProxyAction::class);
        $group->delete('/galeries/{id}[/]', ProxyAction::class);
        $group->patch('/galeries/{id}/photos[/]', ProxyAction::class);
        $group->delete('/galeries/{id}/photos/{photoId}[/]', ProxyAction::class);
        
        // Stockage
        $group->get('/stockage[/]', ProxyAction::class);
        $group->post('/stockage[/]', ProxyAction::class);
        $group->delete('/stockage/{id}[/]', ProxyAction::class);
        
        // Profil
        $group->get('/profil[/]', ProxyAction::class);
        $group->put('/profil[/]', ProxyAction::class);
    })->add(new JwtAuthMiddleware());
};
