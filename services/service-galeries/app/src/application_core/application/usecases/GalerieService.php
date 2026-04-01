<?php 
namespace photopro\galeries\core\application\usecases;
use photopro\galeries\core\application\dto\GalerieDTO;
use photopro\galeries\core\application\ports\repositories\GalerieRepositoryInterface;
use photopro\galeries\core\application\ports\services\GalerieServiceInterface;
use photopro\galeries\core\domain\entities\Galerie;
use photopro\galeries\core\domain\entities\GaleriePhoto;

class GalerieService implements GalerieServiceInterface
{
    private GalerieRepositoryInterface $galerieRepository;

    public function __construct(GalerieRepositoryInterface $galerieRepository)
    {
        $this->galerieRepository = $galerieRepository;
    }

    public function createGalerie(GalerieDTO $galerieDTO): Galerie
    {
        return $this->galerieRepository->create($galerieDTO);
    }

    public function addPhotoToGalerie(GaleriePhoto $galeriePhoto): void
    {
        $this->galerieRepository->addPhotoToGalerie($galeriePhoto);
    }

    public function deletePhotoFromGalerie(string $galerieId, string $photoId): void
    {
        $this->galerieRepository->deletePhotoFromGalerie($photoId, $galerieId);
    }
}