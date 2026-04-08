<?php

namespace photopro\galeries\core\application\usecases;

use photopro\galeries\core\application\ports\repositories\GalerieRepositoryInterface;
use photopro\galeries\infrastructure\http\HttpNotifieClient;
use photopro\notifications\core\application\dto\GaliriePriveeDTO;

/**
 * Use case : publier une galerie.
 *
 * Délègue au repository qui vérifie qu'au moins une photo est présente
 * et que la galerie appartient bien au photographe demandé.
 * Envoie un événement au service de notifications avec les données de la galerie privée.
 */
class PublishGalerieUseCase
{
    private GalerieRepositoryInterface $repo;
    private HttpNotifieClient $notifieClient;

    public function __construct(GalerieRepositoryInterface $repo, HttpNotifieClient $notifieClient)
    {
        $this->repo = $repo;
        $this->notifieClient = $notifieClient;
    }

    /**
     * @throws \Exception Si la galerie est vide ou n'appartient pas à $userId
     */
    public function execute(string $galleryId, string $userId): void
    {
        $this->repo->publishGallery($galleryId, $userId);
        
        $galleryData = $this->repo->getGalleryPreview($galleryId, $userId);
        
        $gallery = new GaliriePriveeDTO(
            galerie_id: $galleryId,
            galerie_titre: $galleryData['galerie']['titre'] ?? '',
            email_client: $galleryData['galerie']['email_client'] ?? '',
            code_acces: $galleryData['galerie']['code_acces'] ?? '',
            url_acces: $galleryData['galerie']['url_acces'] ?? ''
        );
        
        $this->notifieClient->send([
            'typeEvenement' => 'PUBLISHED',
            'galerieId' => $galleryId,
            'galerieTitre' => $gallery->galerie_titre,
            'emailClient' => $gallery->email_client,
            'codeAcces' => $gallery->code_acces,
            'urlAcces' => $gallery->url_acces
        ]);
    }
}