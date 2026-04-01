<?php

namespace app\src\application_core\application\usecases;

use app\src\application_core\application\ports\repositories\GalerieRepositoryInterface;

class PreviewGalerieUseCase
{
    private GalerieRepositoryInterface $repo;

    public function __construct(GalerieRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function execute(string $galleryId, string $userId): ?array
    {
        $rows = $this->repo->getGalleryPreview($galleryId, $userId);

        if (empty($rows)) {
            return null;
        }

        return $this->mapGallery($rows);
    }

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