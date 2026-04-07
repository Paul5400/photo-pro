<?php
namespace photopro\galeries\core\application\ports\repositories;
use photopro\galeries\core\application\dto\GalerieDTO;
use photopro\galeries\core\domain\entities\Galerie;
use photopro\galeries\core\domain\entities\GaleriePhoto;

interface GalerieRepositoryInterface
{
    public function create(GalerieDTO $galerie): Galerie;
    public function createGaleriePrivee(string $galerieId,string $nomClient,string $emailClient,?string $telephone):void;

    public function addPhotoToGalerie(GaleriePhoto $galeriePhoto, string $photographeId): void;

    public function deletePhotoFromGalerie(string $photoId, string $galerieId, string $photographeId): void;

    public function getGalleryPreview(string $galleryId, string $userId): array;

    public function publishGallery(string $galleryId, string $userId): void;

    public function unpublishGallery(string $galleryId, string $userId): void;

    /**
     * Retourne titre + données galerie_privée pour composer la notification AMQP.
     * @return array{titre: string, email_client: string|null, url_acces: string|null, code_acces: string|null}
     */
    public function getGalerieForNotification(string $galleryId): array;

    /**
     * Retourne statut, type et code_acces pour le use case commentaire.
     * @return array{statut: string, type: string, code_acces: string|null}|null
     */
    public function getGalerieForComment(string $galerieId): ?array;

    public function isPhotoInGalerie(string $galerieId, string $photoId): bool;

    public function addCommentaire(string $galerieId, string $photoId, ?string $auteur, string $contenu): string;

    /**
     * Liste les galeries d'un photographe (backoffice).
     * @return array<int, array{id: string, titre: string, type: string, statut: string, mode_mise_en_page: string, created_at: string, published_at: string|null, nb_photos: int}>
     */
    public function findByPhotographe(string $photographeId): array;

    /**
     * Retourne une galerie publiée avec ses photos (frontoffice).
     * Lève une \RuntimeException si non trouvée, non publiée, ou code invalide.
     * @return array{galerie: array, photos: array}
     */
    public function findPublishedById(string $galerieId, ?string $codeAcces): array;
}
