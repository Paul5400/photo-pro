<?php

namespace photopro\galeries\api\actions\galeries;

use photopro\galeries\api\traits\JwtDecoderTrait;
use photopro\galeries\core\application\ports\repositories\GalerieRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UnpublishGalerieAction
{
    use JwtDecoderTrait;
    public function __construct(
        private GalerieRepositoryInterface $galerieRepository
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

            $this->galerieRepository->unpublishGallery($galleryId, $userId);

            $response->getBody()->write(json_encode(['message' => 'Galerie dépubliée avec succès']));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (\Throwable $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }

}