<?php

namespace photopro\galeries\core\application\usecases;

use photopro\galeries\core\application\ports\repositories\GalerieRepositoryInterface;

/**
 * Use case : publier une galerie.
 *
 * Délègue au repository qui vérifie qu'au moins une photo est présente
 * et que la galerie appartient bien au photographe demandé.
 */
class PublishGalerieUseCase
{
    private GalerieRepositoryInterface $repo;

    public function __construct(GalerieRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    /**
     * @throws \Exception Si la galerie est vide ou n'appartient pas à $userId
     */
    public function execute(string $galleryId, string $userId): void
    {
        $this->repo->publishGallery($galleryId, $userId);
    }
}