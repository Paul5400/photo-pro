<?php

use photopro\auth\api\actions\auth\CreatePhotographeAction;
use photopro\auth\api\actions\auth\GetAllPhotographesAction;
use photopro\auth\api\actions\auth\GetPhotographeByIdAction;
use photopro\auth\api\actions\auth\PhotographeLoginAction;
use photopro\auth\api\actions\auth\RegisterAction;
use photopro\auth\core\application\ports\api\PhotographeServiceInterface;
use photopro\auth\core\application\ports\api\AuthnServiceInterface;

return [
     CreatePhotographeAction::class => function ($c) {
        return new CreatePhotographeAction($c->get(PhotographeServiceInterface::class));
     },

     GetAllPhotographesAction::class => function ($c) {
        return new GetAllPhotographesAction($c->get(PhotographeServiceInterface::class));
     },

     GetPhotographeByIdAction::class => function ($c) {
        return new GetPhotographeByIdAction($c->get(PhotographeServiceInterface::class));
     },

    RegisterAction::class => function ($c) {
        return new RegisterAction($c->get(AuthnServiceInterface::class));
    },

    PhotographeLoginAction::class => function ($c) {
        return new PhotographeLoginAction($c->get(AuthnServiceInterface::class));
    },
 ];
