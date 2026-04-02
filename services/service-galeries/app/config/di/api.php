<?php
declare(strict_types=1);
use photopro\galeries\api\actions\galeries\AddPhotoGalerieAction;
use photopro\galeries\api\actions\galeries\CreateGalerieAction;
use photopro\galeries\api\actions\galeries\DeletePhotoFromGalerieAction;
use photopro\galeries\core\application\ports\repositories\GalerieRepositoryInterface;
use photopro\galeries\core\application\ports\services\GalerieServiceInterface;
use photopro\galeries\core\application\usecases\GalerieService;

return [
    // action
    CreateGalerieAction::class => function ($c) {
        return new CreateGalerieAction($c->get(GalerieServiceInterface::class));
    },
    AddPhotoGalerieAction::class => function ($c) {
        return new AddPhotoGalerieAction($c->get(GalerieServiceInterface::class));
    },
    DeletePhotoFromGalerieAction::class => function ($c) {
        return new DeletePhotoFromGalerieAction($c->get(GalerieServiceInterface::class));
    },
    // service
    GalerieServiceInterface::class => function ($c) {
        return new GalerieService($c->get(GalerieRepositoryInterface::class));
    }

];
