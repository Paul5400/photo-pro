<?php

namespace photopro\galeries\core\application\usecases;

use photopro\galeries\core\application\ports\repositories\GalerieRepositoryInterface;

/**
 * Use case : dépublier une galerie.
 *
 * Repasse la galerie en statut "brouillon" et efface published_at.
 * Vérifie que la galerie appartient bien au photographe demandé.
 */
class UnpublishGalerieUseCase
{
    private GalerieRepositoryInterface $repo;

    public function __construct(GalerieRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    /**
     * @throws \Exception Si la galerie n'appartient pas à $userId
     */
    public function execute(string $galleryId, string $userId): void
    {
        $this->repo->unpublishGallery($galleryId, $userId);
    }
}