<?php 
namespace photopro\galeries\core\application\usecases;
use photopro\galeries\core\application\dto\GalerieDTO;
use photopro\galeries\core\application\ports\repositories\GalerieRepositoryInterface;
use photopro\galeries\core\application\ports\services\GalerieServiceInterface;
use photopro\galeries\core\domain\entities\Galerie;
use photopro\galeries\core\domain\entities\GaleriePhoto;

/**
 * Implémentation du service applicatif pour la gestion des galeries.
 *
 * Orchestre les opérations métier : création, ajout/suppression de photos.
 * Pour les galeries privées, génère automatiquement un enregistrement
 * dans la table galerie_privee (code d'accès + URL d'accès).  
 */
class GalerieService implements GalerieServiceInterface
{
    private GalerieRepositoryInterface $galerieRepository;

    public function __construct(GalerieRepositoryInterface $galerieRepository)
    {
        $this->galerieRepository = $galerieRepository;
    }

    /**
     * Crée une galerie et, si elle est de type "privée",
     * génère le code d'accès client dans galerie_privee.
     */
    public function createGalerie(GalerieDTO $galerieDTO): array
    {
        $galerie = $this->galerieRepository->create($galerieDTO);
        $codeAcces = null;

        // Une galerie privée nécessite un accès sécurisé par code pour le client
        if ($galerieDTO->getType() === "privée") {
            $codeAcces = $this->galerieRepository->createGaleriePrivee(
                $galerie->getId()->toString(),
                $galerieDTO->getNomClient(),
                $galerieDTO->getEmailClient(),
                $galerieDTO->getTelephoneClient()
            );
        }

        return ['galerie' => $galerie, 'code_acces' => $codeAcces];
    }

    /**
     * Associe une photo à une galerie (insertion dans galerie_photo).
     */
    public function addPhotoToGalerie(GaleriePhoto $galeriePhoto): void
    {
        $this->galerieRepository->addPhotoToGalerie($galeriePhoto);
    }

    /**
     * Retire une photo d'une galerie (suppression de l'association).
     * Ne supprime pas la photo dans S3.
     */
    public function deletePhotoFromGalerie(string $galerieId, string $photoId): void
    {
        $this->galerieRepository->deletePhotoFromGalerie($photoId, $galerieId);
    }
}