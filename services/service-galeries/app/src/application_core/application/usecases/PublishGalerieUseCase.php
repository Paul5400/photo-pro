<?php

namespace photopro\galeries\core\application\usecases;

use photopro\galeries\core\application\ports\repositories\GalerieRepositoryInterface;

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