<?php

declare(strict_types=1);

namespace photopro\backoffice\api\middleware;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class JwtAuthMiddleware
{
    public function __construct(private Client $authClient) {}

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader) || !preg_match('/^Bearer\s+(.+)$/i', $authHeader)) {
            return $this->unauthorized('Token JWT manquant ou format invalide');
        }

        try {
            $validateResponse = $this->authClient->post('/auth/validate', [
                'headers'     => ['Authorization' => $authHeader],
                'http_errors' => false,
            ]);
        } catch (GuzzleException $e) {
            return $this->unauthorized('Service d\'authentification indisponible');
        }

        if ($validateResponse->getStatusCode() !== 200) {
            $body = json_decode($validateResponse->getBody()->getContents(), true);
            return $this->unauthorized($body['error'] ?? 'Token invalide ou expiré');
        }

        $payload = json_decode($validateResponse->getBody()->getContents(), true);

        $request = $request
            ->withAttribute('user_id', $payload['user_id'] ?? null)
            ->withAttribute('role', $payload['role'] ?? null)
            ->withHeader('X-User-Id', $payload['user_id'] ?? '')
            ->withHeader('X-User-Role', $payload['role'] ?? '');

        return $handler->handle($request);
    }

    private function unauthorized(string $message): Response
    {
        $response = new SlimResponse();
        $response->getBody()->write(json_encode([
            'error' => 'Unauthorized',
            'message' => $message,
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
    }
}
