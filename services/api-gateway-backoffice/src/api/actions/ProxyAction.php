<?php

declare(strict_types=1);

namespace photopro\backoffice\api\actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Psr\Container\ContainerInterface;

class ProxyAction
{
    private ContainerInterface $container;
    private bool $mockMode;

    public function __construct(ContainerInterface $container, bool $mockMode = true)
    {
        $this->container = $container;
        $this->mockMode = $mockMode;
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();

        // Router vers le bon microservice
        $client = $this->resolveClient($path);

        $targetPath = $path;
        if (str_starts_with($path, '/stockage/upload')) {
            $targetPath = '/upload';
        }

        $options = [
            'headers' => $this->filterHeaders($request->getHeaders()),
        ];
        $query = $request->getQueryParams();
        if (!empty($query)) {
            $options['query'] = $query;
        }

        // --- GESTION DES FICHIERS UPLOADES (MULTIPART) ---
        $uploadedFiles = $request->getUploadedFiles();
        if (!empty($uploadedFiles)) {
            unset($options['headers']['Content-Type'], $options['headers']['content-type']);
            $multipart = [];

            // Ajouter les champs du corps de la requête (parsed body)
            $parsedBody = $request->getParsedBody();
            if (is_array($parsedBody)) {
                foreach ($parsedBody as $key => $value) {
                    $multipart[] = [
                        'name'     => $key,
                        'contents' => (string)$value,
                    ];
                }
            }

            // Ajouter les fichiers
            foreach ($uploadedFiles as $name => $file) {
                if ($file->getError() === UPLOAD_ERR_OK) {
                    $multipart[] = [
                        'name'     => $name,
                        'contents' => $file->getStream()->getContents(),
                        'filename' => $file->getClientFilename(),
                    ];
                }
            }
            $options['multipart'] = $multipart;
        } else {
            $parsedBody = $request->getParsedBody();
            $contentType = $request->getHeaderLine('Content-Type');

            if (!empty($parsedBody) && str_contains(strtolower($contentType), 'application/json')) {
                $options['json'] = $parsedBody;
                // Retire Content-Type pour que Guzzle le gère proprement avec l'option json
                unset($options['headers']['Content-Type'], $options['headers']['content-type']);
            } elseif (!empty($parsedBody) && is_array($parsedBody)) {
                $options['form_params'] = $parsedBody;
                unset($options['headers']['Content-Type'], $options['headers']['content-type']);
            } else {
                $request->getBody()->rewind();
                $body = (string)$request->getBody();
                if ($body !== '') {
                    $options['body'] = $body;
                }
            }
        }

        // --- GESTION DU MODE MOCK (Défini dans dependencies.php) ---
        if ($this->mockMode) {
            $mockResponse = [
                'gateway' => 'Backoffice',
                'status' => 'OK',
                'message' => 'Réponse simulée par la Gateway Back pour : ' . $path,
                'method' => $method,
                'path' => $path,
                'data' => [
                    'info' => 'Ce microservice sera développé bientôt.'
                ],
                'headers' => $options['headers'] ?? [],
                'multipart' => isset($options['multipart']) ? count($options['multipart']) : 0
            ];
            $response->getBody()->write(json_encode($mockResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }
        // ------------------------------------------------------------------------

        try {
            $responseMicroservice = $client->request($method, $targetPath, $options);
            $response->getBody()->write($responseMicroservice->getBody()->getContents());
            return $this->withUpstreamHeaders($response, $responseMicroservice);
        } catch (ClientException $e) {
            $upstream = $e->getResponse();
            if ($upstream === null) {
                return $this->handleInternalError($response, $e->getMessage());
            }
            $response->getBody()->write($upstream->getBody()->getContents());
            return $this->withUpstreamHeaders($response, $upstream);
        } catch (\Exception $e) {
            return $this->handleInternalError($response, $e->getMessage());
        }
    }

    private function handleInternalError(Response $response, string $message): Response
    {
        $response->getBody()->write(json_encode([
            'error' => 'Bad Gateway',
            'message' => 'Le microservice est indisponible ou a renvoyé une réponse invalide.',
            'details' => $message
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(502);
    }

    private function resolveClient(string $path): Client
    {
        if (
            str_starts_with($path, '/auth') ||
            str_starts_with($path, '/photographes')
        ) {
            return $this->container->get('auth.client');
        }

        if (
            str_starts_with($path, '/stockage')
            || str_starts_with($path, '/photos')
        ) {
            return $this->container->get('stockage.client');
        }

        return $this->container->get('gallery.client');
    }

    private function filterHeaders(array $headers): array
    {
        $blocked = ['host', 'content-length'];
        $filtered = [];
        foreach ($headers as $name => $values) {
            if (in_array(strtolower($name), $blocked, true)) {
                continue;
            }
            $filtered[$name] = $values;
        }
        return $filtered;
    }

    private function withUpstreamHeaders(Response $response, Response $upstream): Response
    {
        $response = $response->withStatus($upstream->getStatusCode());
        $contentType = $upstream->getHeaderLine('Content-Type');
        if ($contentType !== '') {
            $response = $response->withHeader('Content-Type', $contentType);
        }
        foreach ($upstream->getHeader('Set-Cookie') as $cookieHeader) {
            $response = $response->withAddedHeader('Set-Cookie', $cookieHeader);
        }
        $location = $upstream->getHeaderLine('Location');
        if ($location !== '') {
            $response = $response->withHeader('Location', $location);
        }
        return $response;
    }
}
