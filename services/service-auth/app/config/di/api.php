<?php

use photopro\auth\api\actions\photographe\CreatePhotographeAction;
use photopro\auth\api\actions\photographe\GetAllPhotographesAction;
use photopro\auth\api\actions\photographe\GetPhotographeByIdAction;
use photopro\auth\core\application\ports\api\PhotographeServiceInterface;

// return [
//     CreatePhotographeAction::class => function ($c) {
//         return new CreatePhotographeAction($c->get(PhotographeServiceInterface::class));
//     },

//     GetAllPhotographesAction::class => function ($c) {
//         return new GetAllPhotographesAction($c->get(PhotographeServiceInterface::class));
//     },

//     GetPhotographeByIdAction::class => function ($c) {
//         return new GetPhotographeByIdAction($c->get(PhotographeServiceInterface::class));
//     },
// ];
