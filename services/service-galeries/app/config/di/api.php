<?php
declare(strict_types=1);
use photopro\galeries\api\actions\galeries\AddPhotoGalerieAction;
use photopro\galeries\api\actions\galeries\CreateGalerieAction;
use photopro\galeries\api\actions\galeries\DeletePhotoFromGalerieAction;
use photopro\galeries\api\actions\galeries\PreviewGalerieAction;
use photopro\galeries\api\actions\galeries\PublishGalerieAction;
use photopro\galeries\api\actions\galeries\UnpublishGalerieAction;
use photopro\galeries\core\application\ports\repositories\GalerieRepositoryInterface;
use photopro\galeries\core\application\ports\services\GalerieServiceInterface;
use photopro\galeries\core\application\usecases\GalerieService;
use photopro\galeries\core\application\usecases\PreviewGalerieUseCase;
use photopro\galeries\core\application\usecases\PublishGalerieUseCase;
use photopro\galeries\core\application\usecases\UnpublishGalerieUseCase;

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
    PreviewGalerieAction::class => function ($c) {
        return new PreviewGalerieAction($c->get(PreviewGalerieUseCase::class));
    },
    PublishGalerieAction::class => function ($c) {
        return new PublishGalerieAction($c->get(PublishGalerieUseCase::class));
    },
    UnpublishGalerieAction::class => function ($c) {
        return new UnpublishGalerieAction($c->get(UnpublishGalerieUseCase::class));
    },
    // use cases
    PreviewGalerieUseCase::class => function ($c) {
        return new PreviewGalerieUseCase($c->get(GalerieRepositoryInterface::class));
    },
    PublishGalerieUseCase::class => function ($c) {
        return new PublishGalerieUseCase($c->get(GalerieRepositoryInterface::class));
    },
    UnpublishGalerieUseCase::class => function ($c) {
        return new UnpublishGalerieUseCase($c->get(GalerieRepositoryInterface::class));
    },
    // service
    GalerieServiceInterface::class => function ($c) {
        return new GalerieService($c->get(GalerieRepositoryInterface::class));
    }

];
