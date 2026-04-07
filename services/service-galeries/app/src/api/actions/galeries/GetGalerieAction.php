<?php

namespace photopro\galeries\api\actions\galeries;

use photopro\galeries\core\application\ports\repositories\GalerieRepositoryInterface;
use photopro\galeries\core\application\ports\services\StorageClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class GetGalerieAction
{
    public function __construct(
        private GalerieRepositoryInterface $galerieRepository,
        private StorageClientInterface     $storageClient
    ) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $galleryId  = $args['id'];
            $queryParams = $request->getQueryParams();
            $codeAcces  = $queryParams['code_acces'] ?? null;

            $rows = $this->galerieRepository->getGalleryForVisitor($galleryId);

            if (empty($rows)) {
                $response->getBody()->write(json_encode(['error' => 'Galerie non trouvée ou non publiée']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Vérification code d'accès pour galeries privées
            $type = strtolower(trim($rows[0]['type']));
            if (in_array($type, ['privée', 'privee', 'private'], true)) {
                if (empty($codeAcces)) {
                    $response->getBody()->write(json_encode(['error' => 'Code d\'accès requis pour cette galerie privée']));
                    return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
                }

                $storedCode = $rows[0]['code_acces'] ?? null;
                if ($storedCode === null || !hash_equals($storedCode, $codeAcces)) {
                    $response->getBody()->write(json_encode(['error' => 'Code d\'accès invalide']));
                    return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
                }
            }

            // Construire la réponse
            $first  = $rows[0];
            $result = [
                'galerie' => [
                    'id'               => $first['galerie_id'],
                    'titre'            => $first['titre'],
                    'description'      => $first['description'],
                    'type'             => $first['type'],
                    'mode_mise_en_page' => $first['mode_mise_en_page'],
                    'published_at'     => $first['published_at'],
                ],
                'photos' => [],
            ];

            // Générer une URL pré-signée par photo (jwtToken null = appel interne)
            foreach ($rows as $row) {
                if (empty($row['photo_id'])) {
                    continue;
                }
                $url = $this->storageClient->getPresignedUrl($row['photo_id']);
                $result['photos'][] = [
                    'id'    => $row['photo_id'],
                    'titre' => $row['photo_titre'],
                    'url'   => $url,
                ];
            }

            $response->getBody()->write(json_encode($result));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (\Throwable $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
