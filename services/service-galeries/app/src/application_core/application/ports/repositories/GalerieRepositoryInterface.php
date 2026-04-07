<?php
namespace photopro\galeries\core\application\ports\repositories;
use photopro\galeries\core\application\dto\GalerieDTO;
use photopro\galeries\core\domain\entities\Galerie;
use photopro\galeries\core\domain\entities\GaleriePhoto;

interface GalerieRepositoryInterface
{
    public function create(GalerieDTO $galerie): Galerie;
    public function createGaleriePrivee(string $galerieId,string $nomClient,string $emailClient,?string $telephone):void;
    public function addPhotoToGalerie(GaleriePhoto $galeriePhoto): void;

    public function deletePhotoFromGalerie(string $photoId, string $galerieId): void;

    public function getGalleryPreview(string $galleryId, string $userId): array;

    public function publishGallery(string $galleryId, string $userId): void;

    public function unpublishGallery(string $galleryId, string $userId): void;

    /**
     * Retourne la galerie + ses photos pour un visiteur (galerie publiée uniquement).
     * Inclut code_acces pour les galeries privées (à vérifier dans l'action, ne pas exposer).
     */
    public function getGalleryForVisitor(string $galleryId): array;
}
