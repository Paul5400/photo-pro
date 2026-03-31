<?php

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
    }

    public function __invoke(Request $request, Response $response): Response
    {
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