<?php

declare(strict_types=1);

namespace photopro\auth\api\middlewares;

use Exception;
use photopro\auth\core\application\ports\api\provider\AuthProviderInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class AuthnMiddleware
{
    private AuthProviderInterface $authProvider;

    public function __construct(AuthProviderInterface $authProvider)
    {
        $this->authProvider = $authProvider;
    }

    public function __invoke(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {

        if (!$request->hasHeader('Authorization')) {
            throw new Exception('Authorization header manquant');
        }

        $authHeader = $request->getHeaderLine('Authorization');
        $token = sscanf($authHeader, 'Bearer %s')[0] ?? null;

        if (!$token) {
            throw new Exception('Format Authorization invalide');
        }

        try {
            $payload = $this->authProvider->validateToken($token);

            if (!$payload) {
                throw new Exception('Token invalide ou expiré');
            }

            $request = $request->withAttribute('user_id', $payload['sub'] ?? null);
            $request = $request->withAttribute('role', $payload['role'] ?? null);
            $request = $request->withAttribute('user', $payload['user'] ?? null);
        } catch (Exception $e) {
            $response = new Response();
            $response->getBody()->write(json_encode([
                'error' => 401,
                'message' => 'Authentication failed: ' . $e->getMessage(),
            ]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        return $handler->handle($request);
    }
}
