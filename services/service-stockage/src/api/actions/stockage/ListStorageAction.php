<?php

namespace storage\api\actions\stockage;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use storage\application_core\ports\PhotoRepositoryInterface;
use Psr\Log\LoggerInterface;

class ListStorageAction
{
    public function __construct(
        private PhotoRepositoryInterface $photoRepository,
        private LoggerInterface          $logger
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $photographeId = $user['user']['id'] ?? ($user['sub'] ?? null);

        if (!$photographeId) {
            $response->getBody()->write(json_encode(['error' => 'Utilisateur non identifié']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        try {
            $photos = $this->photoRepository->findByPhotographeId($photographeId);

            $response->getBody()->write(json_encode([
                'photographe_id' => $photographeId,
                'total'          => count($photos),
                'photos'         => $photos,
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $this->logger->error('ListStorageAction Error: ' . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Erreur lors de la récupération des photos']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
