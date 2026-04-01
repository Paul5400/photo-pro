<?php

declare(strict_types=1);

namespace photopro\auth\api\middlewares;

use photopro\auth\core\application\ports\api\provider\AuthProviderInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Psr7\Response;

class AuthnMiddleware implements MiddlewareInterface
{
    private AuthProviderInterface $authProvider;

    public function __construct(AuthProviderInterface $authProvider)
    {
        $this->authProvider = $authProvider;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {

        if (!$request->hasHeader('Authorization')) {
            return $this->unauthorizedResponse('Authorization header manquant');
        }

        $authHeader = $request->getHeaderLine('Authorization');
        $token = sscanf($authHeader, 'Bearer %s')[0] ?? null;

        if (!$token) {
            return $this->unauthorizedResponse('Format Authorization invalide');
        }

        $payload = $this->authProvider->validateToken($token);

        if (!$payload) {
            return $this->unauthorizedResponse('Token invalide ou expiré');
        }

        $request = $request->withAttribute('user_id', $payload['sub'] ?? null);
        $request = $request->withAttribute('user', $payload['user'] ?? null);

        return $handler->handle($request);
    }

    private function unauthorizedResponse(string $message): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(json_encode([
            'error' => 'Non autorisé',
            'message' => $message,
        ]));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
}
