<?php

declare(strict_types=1);

namespace photopro\auth\api\actions\auth;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use photopro\auth\core\application\ports\api\provider\AuthProviderInterface;
use Slim\Psr7\Response;

class ValidateTokenAction
{
    public function __construct(
        private AuthProviderInterface $authProvider
    ) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!$authHeader || !preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            $response->getBody()->write(json_encode(['error' => 'Token manquant ou format invalide']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $payload = $this->authProvider->validateToken($matches[1]);

        if (!$payload) {
            $response->getBody()->write(json_encode(['error' => 'Token invalide ou expiré']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        if (isset($payload['type']) && $payload['type'] === 'refresh') {
            $response->getBody()->write(json_encode(['error' => 'Un refresh token ne peut pas être utilisé ici']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode([
            'user_id' => $payload['sub'] ?? null,
            'role'    => $payload['role'] ?? null,
        ]));

        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    }
}
