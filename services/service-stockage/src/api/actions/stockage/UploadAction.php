<?php

namespace storage\api\actions\stockage;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use storage\infrastructure\storage\StorageService;
use Psr\Log\LoggerInterface;

class UploadAction
{
    private StorageService $storageService;
    private LoggerInterface $logger;

    public function __construct(StorageService $storageService, LoggerInterface $logger)
    {
        $this->storageService = $storageService;
        $this->logger = $logger;
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $uploadedFiles = $request->getUploadedFiles();

        if (empty($uploadedFiles['image'])) {
            $response->getBody()->write(json_encode(['error' => 'No image uploaded']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        /** @var \Slim\Psr7\UploadedFile $uploadedFile */
        $uploadedFile = $uploadedFiles['image'];

        // On récupère l'utilisateur depuis le JWT (injecté par le middleware en tant que tableau)
        $user = $request->getAttribute('user');
        
        $userId = 'anonymous';
        if ($user) {
            // Conversion forcée en tableau de tout l'objet pour être tranquille
            $userArray = json_decode(json_encode($user), true);
            $userId = $userArray['user']['id'] ?? ($userArray['sub'] ?? 'anonymous');
        }

        try {
            $filename = uniqid() . '-' . $uploadedFile->getClientFilename();
            $path = "users/{$userId}/{$filename}";

            $this->storageService->upload(
                $path,
                (string)$uploadedFile->getStream(),
                $uploadedFile->getClientMediaType()
            );

            // On génère une URL présignée pour l'accès immédiat
            $url = $this->storageService->getPresignedUrl($path);

            $data = [
                'message' => 'Upload successful',
                'path' => $path,
                'url' => $url,
                'user_id' => $userId
            ];

            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

        } catch (\Exception $e) {
            $this->logger->error("UploadAction Error: " . $e->getMessage());
            $response->getBody()->write(json_encode(['error' => 'Internal Server Error']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
