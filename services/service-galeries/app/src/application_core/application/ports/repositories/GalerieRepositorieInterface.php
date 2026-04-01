<?php
namespace photopro\galeries\core\application\ports\repositories;
use photopro\galeries\core\application\dto\GalerieDTO;
use photopro\galeries\core\domain\entities\Galerie;
use photopro\galeries\core\domain\entities\GaleriePhoto;

interface GalerieRepositoryInterface
{
    public function create(GalerieDTO $galerie): Galerie;

    public function addPhotoToGalerie(GaleriePhoto $galeriePhoto): void;

    public function deletePhotoFromGalerie(string $id): void;
}
