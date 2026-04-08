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

    /**
     * Liste les galeries.
     */
    public function getGaleries(?string $photographeId): array;

    /**
     * Retourne le statut et le type d'une galerie.
     * @return array{statut: string, type: string}|null null si non trouvée
     */
    public function getGalerieStatutEtType(string $galerieId): ?array;

    /**
     * Retourne le code d'accès d'une galerie privée.
     * @return string|null null si aucun code trouvé
     */
    public function getCodeAcces(string $galerieId): ?string;

    /**
     * Insère un commentaire sur une photo d'une galerie.
     * @return string L'UUID du commentaire créé
     */
    public function addCommentaire(string $galerieId, string $photoId, string $auteur, string $contenu): string;

    /**
     * Récupère tous les commentaires d'une photo dans une galerie donnée.
     * @return array Tableau de commentaires
     */
    public function getCommentairesByPhoto(string $galerieId, string $photoId): array;
}
