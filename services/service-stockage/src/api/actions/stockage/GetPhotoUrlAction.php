<?php

namespace storage\api\actions\stockage;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use storage\application_core\ports\PhotoRepositoryInterface;
use storage\infrastructure\storage\StorageService;
use Psr\Log\LoggerInterface;

class GetPhotoUrlAction
{
    public function __construct(
        private PhotoRepositoryInterface $photoRepository,
        private StorageService           $storageService,
        private LoggerInterface          $logger
    ) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $id = $args['id'] ?? null;

        if (!$id) {
            $response->getBody()->write(json_encode(['error' => 'ID manquant']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $cheminS3 = $this->photoRepository->findCheminS3ById($id);

        if ($cheminS3 === null) {
            $response->getBody()->write(json_encode(['error' => 'Photo introuvable']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        try {
            $url = $this->storageService->getPresignedUrl($cheminS3);

            $response->getBody()->write(json_encode(['url' => $url]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

        } catch (\Exception $e) {
            $this->logger->error('GetPhotoUrlAction Error: ' . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Impossible de générer l\'URL']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
