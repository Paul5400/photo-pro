<?php
declare(strict_types=1);

namespace photopro\frontoffice\api\actions;

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
        
        $options = [
            'headers' => $this->filterHeaders($request->getHeaders()),
        ];
        $query = $request->getQueryParams();
        if (!empty($query)) {
            $options['query'] = $query;
        }
        $body = (string)$request->getBody();
        if ($body !== '') {
            $options['body'] = $body;
        }

        // --- GESTION DU MODE MOCK (Défini dans le .env global) ---
        if ($this->mockMode) {
            $mockResponse = [
                'gateway_status' => 'OK',
                'message' => 'Réponse simulée par la Gateway Front pour : ' . $path,
                'method' => $method,
                'path' => $path,
                'data' => [
                    'info' => 'Ce microservice sera développé plus tard par les autres équipes.'
                ]
            ];
            $response->getBody()->write(json_encode($mockResponse));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }
        // ------------------------------------------------------------------------

        try {
            $responseMicroservice = $client->request($method, $path, $options);
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
        // if (str_starts_with($path, '/auth') || str_starts_with($path, '/tokens')) {
        //     return $this->container->get('auth.client');
        // }

        if (str_starts_with($path, '/galeries')) {
            return $this->container->get('gallery.client');
        }

        // autres routes à écrire pour rediriger vers les micro services correspondants

        // Default to gallery client if we add other routes related to it
        return $this->container->get('gallery.client');
    }

    /**
     * @param array<string, array<int, string>> $headers
     * @return array<string, array<int, string>>
     */
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
        $location = $upstream->getHeaderLine('Location');
        if ($location !== '') {
            $response = $response->withHeader('Location', $location);
        }
        return $response;
    }
}
