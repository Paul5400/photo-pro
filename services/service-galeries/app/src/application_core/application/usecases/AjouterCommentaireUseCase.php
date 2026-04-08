<?php

namespace photopro\galeries\core\application\usecases;

use photopro\galeries\core\application\ports\repositories\GalerieRepositoryInterface;

/**
 * Cas d'utilisation : ajouter un commentaire à une photo d'une galerie publiée.
 *
 * Règles métier :
 *   - La galerie doit exister et avoir le statut "publie"  → 403 sinon
 *   - Si la galerie est de type "privée", le code_acces doit être fourni et correct → 403 sinon
 *   - auteur et contenu ne doivent pas être vides → 400 sinon
 */
class AjouterCommentaireUseCase
{
    public function __construct(private GalerieRepositoryInterface $repository) {}

    /**
     * @throws \InvalidArgumentException (code 400) si auteur ou contenu vide
     * @throws \DomainException          (code 403) si galerie non publiée ou code_acces invalide
     * @throws \RuntimeException         (code 404) si galerie inexistante
     * @return string UUID du commentaire créé
     */
    public function execute(
        string $galerieId,
        string $photoId,
        string $auteur,
        string $contenu,
        ?string $codeAcces
    ): string {
        if (trim($auteur) === '') {
            throw new \InvalidArgumentException("Le champ 'auteur' est requis.");
        }
        if (trim($contenu) === '') {
            throw new \InvalidArgumentException("Le champ 'contenu' est requis.");
        }

        $galerie = $this->repository->getGalerieStatutEtType($galerieId);

        if ($galerie === null) {
            throw new \RuntimeException("Galerie introuvable.");
        }

        if ($galerie['statut'] !== 'publie') {
            throw new \DomainException("Impossible de commenter une galerie non publiée.");
        }

        $type = strtolower(trim($galerie['type']));
        if (in_array($type, ['privée', 'privee', 'private'], true)) {
            $codeAttendu = $this->repository->getCodeAcces($galerieId);
            if ($codeAcces === null || $codeAcces !== $codeAttendu) {
                throw new \DomainException("Code d'accès invalide ou manquant.");
            }
        }

        return $this->repository->addCommentaire($galerieId, $photoId, trim($auteur), trim($contenu));
    }
}
