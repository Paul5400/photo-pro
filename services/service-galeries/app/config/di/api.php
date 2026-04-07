<?php
declare(strict_types=1);
use photopro\galeries\api\actions\galeries\AddPhotoGalerieAction;
use photopro\galeries\api\actions\galeries\CreateGalerieAction;
use photopro\galeries\api\actions\galeries\DeletePhotoFromGalerieAction;
use photopro\galeries\api\actions\galeries\GetGaleriesAction;
use photopro\galeries\api\actions\galeries\GetGalerieAction;
use photopro\galeries\api\actions\galeries\PreviewGalerieAction;
use photopro\galeries\api\actions\galeries\PublishGalerieAction;
use photopro\galeries\api\actions\galeries\UnpublishGalerieAction;
use photopro\galeries\api\actions\photos\UploadPhotoAction;
use photopro\galeries\core\application\ports\repositories\GalerieRepositoryInterface;
use photopro\galeries\core\application\ports\repositories\PhotoRepositoryInterface;
use photopro\galeries\core\application\ports\services\GalerieServiceInterface;
use photopro\galeries\core\application\ports\services\StorageClientInterface;
use photopro\galeries\core\application\usecases\GalerieService;
use photopro\galeries\core\application\usecases\PreviewGalerieUseCase;
use photopro\galeries\core\application\usecases\PublishGalerieUseCase;
use photopro\galeries\core\application\usecases\UnpublishGalerieUseCase;

return [
    // actions
    CreateGalerieAction::class => function ($c) {
        return new CreateGalerieAction($c->get(GalerieServiceInterface::class));
    },
    GetGaleriesAction::class => function ($c) {
        return new GetGaleriesAction($c->get(GalerieRepositoryInterface::class));
    },
    AddPhotoGalerieAction::class => function ($c) {
        return new AddPhotoGalerieAction($c->get(GalerieServiceInterface::class));
    },
    DeletePhotoFromGalerieAction::class => function ($c) {
        return new DeletePhotoFromGalerieAction($c->get(GalerieServiceInterface::class));
    },
    UploadPhotoAction::class => function ($c) {
        return new UploadPhotoAction(
            $c->get(StorageClientInterface::class),
            $c->get(PhotoRepositoryInterface::class)
        );
    },
    PreviewGalerieAction::class => function ($c) {
        return new PreviewGalerieAction(
            $c->get(GalerieRepositoryInterface::class),
            $c->get(StorageClientInterface::class)
        );
    },
    GetGalerieAction::class => function ($c) {
        return new GetGalerieAction(
            $c->get(GalerieRepositoryInterface::class),
            $c->get(StorageClientInterface::class)
        );
    },
    PublishGalerieAction::class => function ($c) {
        return new PublishGalerieAction(
            $c->get(GalerieRepositoryInterface::class)
        );
    },
    UnpublishGalerieAction::class => function ($c) {
        return new UnpublishGalerieAction(
            $c->get(GalerieRepositoryInterface::class)
        );
    },
    // service
    GalerieServiceInterface::class => function ($c) {
        return new GalerieService($c->get(GalerieRepositoryInterface::class));
    },
];
