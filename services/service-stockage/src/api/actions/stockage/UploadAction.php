<?php

<<<<<<< HEAD
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
=======
namespace photopro\stockage\api\actions\stockage;

use photopro\stockage\infrastructure\storage\StorageService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpBadRequestException;

class UploadAction
{
    private StorageService $storage;

    public function __construct(StorageService $storage)
    {
        $this->storage = $storage;
>>>>>>> origin/feat/StockageS3
    }

    public function __invoke(Request $request, Response $response): Response
    {
<<<<<<< HEAD
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
=======
        // 1. Récupération du fichier uploadé (via Bruno/Postman)
        $uploadedFiles = $request->getUploadedFiles();
        $file = $uploadedFiles['image'] ?? null;

        if (!$file || $file->getError() !== UPLOAD_ERR_OK) {
            throw new HttpBadRequestException($request, "Aucune image reçue ou erreur d'upload.");
        }

        // 2. Validation basique du type
        $mime = $file->getClientMediaType();
        if (!str_starts_with($mime, 'image/')) {
             throw new HttpBadRequestException($request, "Seules les images sont autorisées.");
        }

        // 3. Simulation de l'utilisateur (sera remplacé par le JWT plus tard)
        $userId = "paul_dormeur"; 

        // 4. Stockage physique sur SeaweedFS
        $key = $this->storage->store(
            (string)$file->getStream(), 
            $mime, 
            $userId
        );

        // 5. Génération de l'URL pour la réponse JSON
        $url = $this->storage->getPresignedUrl($key);

        $data = [
            'status' => 'success',
            'data' => [
                'key' => $key,
                'url' => $url
            ]
        ];

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
>>>>>>> origin/feat/StockageS3
