<?php
namespace photopro\galeries\core\application\usecases;

use photopro\galeries\core\application\ports\repositories\GalerieRepositoryInterface;

class AjouterCommentaireUseCase
{
    private GalerieRepositoryInterface $repo;

    public function __construct(GalerieRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function execute(
        string  $galerieId,
        string  $photoId,
        ?string $auteur,
        string  $contenu,
        ?string $codeAcces
    ): string {
        if (trim($contenu) === '') {
            throw new \InvalidArgumentException('Le contenu du commentaire est obligatoire');
        }

        $galerie = $this->repo->getGalerieForComment($galerieId);

        if ($galerie === null) {
            throw new \RuntimeException('Galerie non trouvée');
        }

        if ($galerie['statut'] !== 'publie') {
            throw new \RuntimeException("Cette galerie n'est pas accessible");
        }

        $type = strtolower(trim($galerie['type'] ?? ''));
        if (in_array($type, ['privée', 'privee', 'private'], true)) {
            if (empty($codeAcces) || $galerie['code_acces'] !== $codeAcces) {
                throw new \RuntimeException("Code d'accès invalide");
            }
        }

        if (!$this->repo->isPhotoInGalerie($galerieId, $photoId)) {
            throw new \RuntimeException('Photo non trouvée dans cette galerie');
        }

        return $this->repo->addCommentaire($galerieId, $photoId, $auteur, $contenu);
    }
}
