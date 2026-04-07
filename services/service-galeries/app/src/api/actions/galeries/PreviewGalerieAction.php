<?php

namespace photopro\galeries\api\actions\galeries;

use photopro\galeries\api\traits\JwtDecoderTrait;
use photopro\galeries\core\application\ports\repositories\GalerieRepositoryInterface;
use photopro\galeries\core\application\ports\services\StorageClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PreviewGalerieAction
{
    use JwtDecoderTrait;
    public function __construct(
        private GalerieRepositoryInterface $galerieRepository,
        private StorageClientInterface     $storageClient
    ) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $galleryId = $args['id'];

            $authHeader = $request->getHeaderLine('Authorization');
            $jwtToken   = str_starts_with($authHeader, 'Bearer ') ? substr($authHeader, 7) : '';
            $userId     = $this->extractUserIdFromJwt($jwtToken);

            if (!$userId) {
                $response->getBody()->write(json_encode(['error' => 'Token invalide']));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }

            $rows = $this->galerieRepository->getGalleryPreview($galleryId, $userId);

            if (empty($rows)) {
                $response->getBody()->write(json_encode(['error' => 'Galerie non trouvée']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            // Construire la réponse structurée
            $first  = $rows[0];
            $result = [
                'galerie' => [
                    'id'               => $first['galerie_id'],
                    'titre'            => $first['titre'],
                    'description'      => $first['description'],
                    'type'             => $first['type'],
                    'statut'           => $first['statut'],
                    'mode_mise_en_page' => $first['mode_mise_en_page'],
                    'created_at'       => $first['created_at'],
                    'published_at'     => $first['published_at'],
                ],
                'photos'  => [],
            ];

            // Données accès galerie privée
            if (isset($first['code_acces'])) {
                $result['galerie']['nom_client']       = $first['nom_client'];
                $result['galerie']['email_client']     = $first['email_client'];
                $result['galerie']['telephone_client'] = $first['telephone_client'];
                $result['galerie']['code_acces']       = $first['code_acces'];
                $result['galerie']['url_acces']        = $first['url_acces'];
            }

            // Générer une URL pré-signée par photo
            foreach ($rows as $row) {
                if (empty($row['photo_id'])) {
                    continue;
                }
                $url = $this->storageClient->getPresignedUrl($row['photo_id'], $jwtToken);
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