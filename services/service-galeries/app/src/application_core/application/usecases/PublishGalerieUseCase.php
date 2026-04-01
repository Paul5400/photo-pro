<?php

namespace app\src\application_core\application\usecases;

use app\src\application_core\application\ports\repositories\GalerieRepositoryInterface;

class PublishGalerieUseCase
{
    private GalerieRepositoryInterface $repo;

    public function __construct(GalerieRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function execute(string $galleryId, string $userId): void
    {

        $this->repo->publishGallery($galleryId, $userId);
    }
}