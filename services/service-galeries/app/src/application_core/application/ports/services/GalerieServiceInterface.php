<?php
namespace photopro\galeries\core\application\ports\services;
use photopro\galeries\core\application\dto\GalerieDTO;
use photopro\galeries\core\domain\entities\Galerie;
use photopro\galeries\core\domain\entities\GaleriePhoto;

interface GalerieServiceInterface
{
    public function createGalerie(GalerieDTO $galerieDTO): Galerie;

    public function addPhotoToGalerie(GaleriePhoto $galeriePhoto): void;

    public function deletePhotoFromGalerie(string $galerieId, string $photoId): void;
}