<?php
declare(strict_types=1);

use photopro\auth\api\actions\photographe\CreatePhotographeAction;
use photopro\auth\api\actions\photographe\GetAllPhotographesAction;
use photopro\auth\api\actions\photographe\GetPhotographeByIdAction;

return function (Slim\App $app): Slim\App {
    $app->get('/photographes', GetAllPhotographesAction::class);
    $app->get('/photographes/{id}', GetPhotographeByIdAction::class);
    $app->post('/photographes', CreatePhotographeAction::class);

    return $app;
};
