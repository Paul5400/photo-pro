<?php

declare(strict_types=1);

use photopro\auth\api\actions\auth\CreatePhotographeAction;
use photopro\auth\api\actions\auth\GetAllPhotographesAction;
use photopro\auth\api\actions\auth\GetPhotographeByIdAction;
use photopro\auth\api\actions\auth\PhotographeLoginAction;
use photopro\auth\api\actions\auth\RegisterAction;
use photopro\auth\api\actions\auth\ConnexionVisiteurAction;
use photopro\auth\api\actions\auth\RefreshTokenAction;
use photopro\auth\api\middlewares\AuthnMiddleware;
use photopro\auth\api\middlewares\AuthzPhotographeMiddleware;

return function (Slim\App $app): Slim\App {

    $app->get('/photographes', GetAllPhotographesAction::class)
        ->add(AuthzPhotographeMiddleware::class)
        ->add(AuthnMiddleware::class);

    $app->get('/photographes/{id}', GetPhotographeByIdAction::class)
        ->add(AuthzPhotographeMiddleware::class)
        ->add(AuthnMiddleware::class);

    $app->post('/photographes', CreatePhotographeAction::class);
    $app->post('/auth/register', RegisterAction::class);
    $app->post('/auth/login/photographe', PhotographeLoginAction::class);
    $app->post('/auth/login/visiteur', ConnexionVisiteurAction::class);
    $app->post('/auth/token/refresh', RefreshTokenAction::class);

    return $app;
};
