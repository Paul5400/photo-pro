<?php

namespace photopro\galeries\core\application\usecases;

use photopro\galeries\core\application\ports\repositories\GalerieRepositoryInterface;
use photopro\notifications\core\application\dto\GaliriePriveeDTO;

/**
 * Use case : dépublier une galerie.
 *
 * Repasse la galerie en statut "brouillon" et efface published_at.
 * Vérifie que la galerie appartient bien au photographe demandé.
 */
class UnpublishGalerieUseCase
{
    private GalerieRepositoryInterface $repo;
    private HttpNotifieClient $notifieClient;

    public function __construct(GalerieRepositoryInterface $repo, HttpNotifieClient $notifieClient)
    {
        $this->repo = $repo;
        $this->notifieClient = $notifieClient;
    }

    /**
     * @throws \Exception Si la galerie n'appartient pas à $userId
     */
    public function execute(string $galleryId, string $userId): void
    {
        $this->repo->unpublishGallery($galleryId, $userId);
       $gallery = new GaliriePriveeDTO(
            galerie_id: $galleryId,
            galerie_titre: $galleryData['galerie']['titre'] ?? '',
            email_client: $galleryData['galerie']['email_client'] ?? '',
            code_acces: $galleryData['galerie']['code_acces'] ?? '',
            url_acces: $galleryData['galerie']['url_acces'] ?? ''
        );
        
        $this->notifieClient->send([
            'typeEvenement' => 'UNPUBLISHED',
            'galerieId' => $galleryId,
            'galerieTitre' => $gallery->galerie_titre,
            'emailClient' => $gallery->email_client,
            'codeAcces' => $gallery->code_acces,
            'urlAcces' => $gallery->url_acces
        ]);
    }
}