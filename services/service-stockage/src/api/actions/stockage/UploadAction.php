<?php

namespace storage\api\actions\stockage;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use storage\application_core\ports\PhotoRepositoryInterface;
use storage\infrastructure\storage\StorageService;
use Ramsey\Uuid\Uuid;
use Psr\Log\LoggerInterface;

class UploadAction
{
    public function __construct(
        private StorageService          $storageService,
        private PhotoRepositoryInterface $photoRepository,
        private LoggerInterface          $logger
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $uploadedFiles = $request->getUploadedFiles();

        if (empty($uploadedFiles['image'])) {
            $response->getBody()->write(json_encode(['error' => 'No image uploaded']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        /** @var \Slim\Psr7\UploadedFile $uploadedFile */
        $uploadedFile = $uploadedFiles['image'];

        $user = $request->getAttribute('user');
        $userId = 'anonymous';
        if ($user) {
            $userArray = json_decode(json_encode($user), true);
            $userId = $userArray['user']['id'] ?? ($userArray['sub'] ?? 'anonymous');
        }

        try {
            $photoId  = Uuid::uuid4()->toString();
            $filename = $photoId . '-' . $uploadedFile->getClientFilename();
            $path     = "users/{$userId}/{$filename}";

            $this->storageService->upload(
                $path,
                (string) $uploadedFile->getStream(),
                $uploadedFile->getClientMediaType()
            );

            $url = $this->storageService->getPresignedUrl($path);

            // Calcul de la taille en Mo
            $tailleMo = round($uploadedFile->getSize() / 1048576, 4);

            // Titre : paramètre form optionnel, sinon nom du fichier sans extension
            $parsedBody = $request->getParsedBody();
            $titre = $parsedBody['titre'] ?? pathinfo($uploadedFile->getClientFilename(), PATHINFO_FILENAME);

            $this->photoRepository->save(
                $photoId,
                $titre,
                $uploadedFile->getClientMediaType(),
                $tailleMo,
                $uploadedFile->getClientFilename(),
                $path,
                $userId
            );

            $response->getBody()->write(json_encode([
                'photo_id' => $photoId,
                'url'      => $url,
                'path'     => $path,
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

        } catch (\Exception $e) {
            $this->logger->error('UploadAction Error: ' . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Internal Server Error']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}

