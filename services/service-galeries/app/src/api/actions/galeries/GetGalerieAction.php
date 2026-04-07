<?php

namespace photopro\galeries\api\actions\galeries;

use photopro\galeries\core\application\ports\repositories\GalerieRepositoryInterface;
use photopro\galeries\core\application\ports\services\StorageClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Action GET /galeries/{id}
 *
 * Retourne le détail d'une galerie publiée avec ses photos (URLs pré-signées S3).
 * Règles d'accès :
 *   - Galerie non publiée (brouillon) → 404
 *   - Galerie de type "privée" sans code_acces en query string → 401
 *   - Galerie de type "privée" avec mauvais code_acces → 403
 *   - Galerie publique publiée → 200 (pas de JWT requis)
 *
 * Réponses :
 *   200 - Détail de la galerie + liste des photos
 *   401 - Code d'accès manquant (galerie privée)
 *   403 - Code d'accès invalide
 *   404 - Galerie inexistante ou non publiée
 *   500 - Erreur serveur
 */
class GetGalerieAction
{
    public function __construct(
        private GalerieRepositoryInterface $galerieRepository,
        private StorageClientInterface     $storageClient
    ) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $galleryId   = $args['id'];
            $queryParams = $request->getQueryParams();
            // Code d'accès optionnel (requis uniquement pour les galeries privées)
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
