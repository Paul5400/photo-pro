<?php

namespace photopro\galeries\core\application\ports\services;

interface StorageClientInterface
{
    /**
     * Upload un fichier vers le service-stockage.
     * Retourne [ 'photo_id' => string, 'path' => string ]
     */
    public function upload(string $fileContent, string $filename, string $mimeType, ?string $titre, ?string $jwtToken = null): array;

    /**
     * Récupère une URL présignée fraîche pour un photo_id donné.
     * jwtToken est optionnel (null = appel interne sans auth, ex: consultation visiteur).
     */
    public function getPresignedUrl(string $photoId, ?string $jwtToken = null): string;
}
