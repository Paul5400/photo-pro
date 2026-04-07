<?php

namespace photopro\galeries\api\middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class AuthMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): ResponseInterface
    {
        $authorizationHeader = $request->getHeaderLine('Authorization');

        if (!$authorizationHeader) {
            return $this->unauthorizedResponse('Token manquant');
        }

        if (!preg_match('/^Bearer\s+(.+)$/i', $authorizationHeader, $matches)) {
            return $this->unauthorizedResponse('Format du token invalide');
        }

        $token = $matches[1];

        if (!$this->isValidToken($token)) {
            return $this->unauthorizedResponse('Token invalide');
        }

        return $handler->handle($request);
    }

    private function isValidToken(string $token): bool
    {
        return !empty($token);
    }

    private function unauthorizedResponse(string $message): ResponseInterface
    {
        $response = new Response(401);
        $payload = ['error' => 'Unauthorized', 'message' => $message];
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
