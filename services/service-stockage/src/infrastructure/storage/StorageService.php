<?php

namespace photopro\stockage\infrastructure\storage;

use Aws\S3\S3Client;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class StorageService
{
    private S3Client $s3Internal; // Client pour envoyer les fichiers (réseau Docker interne)
    private S3Client $s3External; // Client pour générer les URLs (réseau public/localhost)
    private string $bucket;
    private LoggerInterface $logger;

    public function __construct(
        S3Client $s3Internal, 
        S3Client $s3External, 
        string $bucket, 
        LoggerInterface $logger
    ) {
        $this->s3Internal = $s3Internal;
        $this->s3External = $s3External;
        $this->bucket = $bucket;
        $this->logger = $logger;
    }

    /**
     * Enregistre un fichier sur SeaweedFS (S3) et retourne sa clé unique
     */
    public function store(string $binaryContent, string $mimeType, string $userId): string
    {
        // Générer un nom de fichier unique (UUID) classé par utilisateur
        $extension = $this->getExtensionFromMime($mimeType);
        $key = "users/{$userId}/" . Uuid::uuid4()->toString() . $extension;

        try {
            // Upload du fichier via le réseau Docker (service: S3)
            $this->s3Internal->putObject([
                'Bucket'      => $this->bucket,
                'Key'         => $key,
                'Body'        => $binaryContent,
                'ContentType' => $mimeType,
            ]);

            return $key;
        } catch (\Exception $e) {
            $this->logger->error("Erreur S3 Upload: " . $e->getMessage());
            throw new \RuntimeException("Impossible de stocker l'image sur le serveur S3.");
        }
    }

    /**
     * Génère une URL temporaire (1h) pour que le client (navigateur) puisse voir l'image
     */
    public function getPresignedUrl(string $key): string
    {
        try {
            // On crée la commande de lecture (Get)
            $cmd = $this->s3External->getCommand('GetObject', [
                'Bucket' => $this->bucket,
                'Key'    => $key,
            ]);

            // On signe l'URL pour qu'elle soit valide 60 minutes
            // Attention : ici on utilise le client "External" (localhost)
            $request = $this->s3External->createPresignedRequest($cmd, '+60 minutes');

            return (string) $request->getUri();
        } catch (\Exception $e) {
            $this->logger->error("Erreur S3 Signature: " . $e->getMessage());
            return ""; // Ou lancer une exception si critique
        }
    }

    /**
     * Helper pour déduire l'extension du fichier
     */
    private function getExtensionFromMime(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => '.jpg',
            'image/png'  => '.png',
            'image/webp' => '.webp',
            default      => '',
        };
    }
}