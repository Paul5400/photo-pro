<?php

namespace photopro\galeries\core\application\usecases;

use photopro\galeries\core\application\ports\repositories\GalerieRepositoryInterface;

/**
 * Use case : aperçu d'une galerie pour le photographe propriétaire.
 *
 * Charge les données brutes (jointure galerie + photos) depuis le repository
 * et les restructure en un tableau galerie / photos prêt pour l'API.
 * Retourne null si la galerie n'existe pas ou n'appartient pas à l'utilisateur.
 */
class PreviewGalerieUseCase
{
    private GalerieRepositoryInterface $repo;

    public function __construct(GalerieRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Exécute le use case.
     *
     * @return array|null Structure { id, titre, ..., photos[] } ou null si introuvable
     */
    public function execute(string $galleryId, string $userId): ?array
    {
        $rows = $this->repo->getGalleryPreview($galleryId, $userId);

        if (empty($rows)) {
            return null;
        }

        return $this->mapGallery($rows);
    }

    /**
     * Transforme les lignes de résultat SQL en structure d'API.
     */
    private function mapGallery(array $rows): array
    {
        $gallery = [
            'id' => $rows[0]['galerie_id'],
            'titre' => $rows[0]['titre'],
            'description' => $rows[0]['description'],
            'type' => $rows[0]['type'],
            'statut' => $rows[0]['statut'],
            'mode_mise_en_page' => $rows[0]['mode_mise_en_page'],
            'created_at' => $rows[0]['created_at'],
            'published_at' => $rows[0]['published_at'],
            'photos' => []
        ];

        foreach ($rows as $row) {
            if ($row['photo_id'] !== null) {
                $gallery['photos'][] = [
                    'id' => $row['photo_id'],
                    'url' => $row['url'],
                    'titre' => $row['photo_titre']
                ];
            }
        }

        return $gallery;
    }
}