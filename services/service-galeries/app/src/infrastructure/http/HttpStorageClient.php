<?php

namespace photopro\galeries\infra\http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use photopro\galeries\core\application\ports\services\StorageClientInterface;

class HttpStorageClient implements StorageClientInterface
{
    private Client $client;

    public function __construct(string $storageUrl)
    {
        $this->client = new Client([
            'base_uri' => $storageUrl,
            'timeout'  => 30.0,
        ]);
    }

    public function upload(string $fileContent, string $filename, string $mimeType, ?string $titre, ?string $jwtToken = null): array
    {
        $multipart = [
            [
                'name'     => 'image',
                'contents' => $fileContent,
                'filename' => $filename,
            ],
        ];

        if ($titre !== null) {
            $multipart[] = [
                'name'     => 'titre',
                'contents' => $titre,
            ];
        }

        $options = ['multipart' => $multipart];

        if ($jwtToken !== null) {
            $options['headers'] = ['Authorization' => "Bearer {$jwtToken}"];
        }

        try {
            $response = $this->client->request('POST', '/upload', $options);
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $body = $e->getResponse()->getBody()->getContents();
            throw new \RuntimeException('Erreur service-stockage: ' . $body);
        }

        $data = json_decode($response->getBody()->getContents(), true);
        if (!is_array($data)) {
            throw new \RuntimeException('Réponse invalide du service-stockage (non-JSON)');
        }
        return $data;
    }

    public function getPresignedUrl(string $photoId, ?string $jwtToken = null): string
    {
        $options = [];
        if ($jwtToken !== null) {
            $options['headers'] = ['Authorization' => "Bearer {$jwtToken}"];
        }

        $response = $this->client->request('GET', "/photos/{$photoId}/url", $options);

        $data = json_decode($response->getBody()->getContents(), true);
        return $data['url'] ?? '';
    }
}
