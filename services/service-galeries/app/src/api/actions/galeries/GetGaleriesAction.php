<?php

namespace photopro\galeries\api\actions\galeries;

use photopro\galeries\core\application\ports\repositories\GalerieRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class GetGaleriesAction
{
    public function __construct(
        private GalerieRepositoryInterface $galerieRepository
    ) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $userId = $request->getHeaderLine('X-User-Id') ?: null;

            $rows = $this->galerieRepository->getGaleries($userId);

            $galeries = array_map(fn($row) => [
                'id'                => $row['id'],
                'titre'             => $row['titre'],
                'description'       => $row['description'],
                'type'              => $row['type'],
                'statut'            => $row['statut'],
                'mode_mise_en_page' => $row['mode_mise_en_page'],
                'created_at'        => $row['created_at'],
                'published_at'      => $row['published_at'],
                'photographe_id'    => $row['photographe_id'],
            ], $rows);

            $response->getBody()->write(json_encode(['galeries' => $galeries]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (\Throwable $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
