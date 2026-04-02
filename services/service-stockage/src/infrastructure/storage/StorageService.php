<?php

namespace storage\infrastructure\storage;

use Aws\S3\S3Client;
use Psr\Log\LoggerInterface;

class StorageService
{
    private S3Client $s3Client;         // Client interne (s3:8333)
    private S3Client $externalS3Client; // Client externe (localhost:8333)
    private string $bucket;
    private LoggerInterface $logger;

    public function __construct(S3Client $s3Client, S3Client $externalS3Client, string $bucket, LoggerInterface $logger)
    {
        $this->s3Client = $s3Client;
        $this->externalS3Client = $externalS3Client;
        $this->bucket = $bucket;
        $this->logger = $logger;
    }

    public function upload(string $path, string $content, string $mimeType): string
    {
        try {
            $this->s3Client->putObject([
                'Bucket' => $this->bucket,
                'Key'    => $path,
                'Body'   => $content,
                'ContentType' => $mimeType,
            ]);

            return $path;
        } catch (\Exception $e) {
            $this->logger->error("S3 Upload Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function getPresignedUrl(string $path): string
    {
        // On utilise le client EXTERNE pour générer une URL en "localhost"
        $cmd = $this->externalS3Client->getCommand('GetObject', [
            'Bucket' => $this->bucket,
            'Key'    => $path
        ]);

        $request = $this->externalS3Client->createPresignedRequest($cmd, '+60 minutes');

        return (string)$request->getUri();
    }
}
