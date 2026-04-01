<?php
declare(strict_types=1);

use photopro\auth\api\actions\auth\CreatePhotographeAction;
use photopro\auth\api\actions\auth\GetAllPhotographesAction;
use photopro\auth\api\actions\auth\GetPhotographeByIdAction;
use photopro\auth\api\actions\auth\PhotographeLoginAction;
use photopro\auth\api\actions\auth\RegisterAction;

return function (Slim\App $app): Slim\App {
    
    $app->get('/photographes', GetAllPhotographesAction::class);
    $app->get('/photographes/{id}', GetPhotographeByIdAction::class);
    $app->post('/photographes', CreatePhotographeAction::class);
    $app->post('/auth/register', RegisterAction::class);
    $app->post('/auth/login/photographe', PhotographeLoginAction::class);

    return $app;
};
