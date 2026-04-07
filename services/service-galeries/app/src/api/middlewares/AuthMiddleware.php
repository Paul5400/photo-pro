<?php
namespace photopro\galeries\api\middlewares;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class AuthMiddleware
{
    private string $jwtSecret;

    public function __construct()
    {
        $this->jwtSecret = getenv('JWT_SECRET') ?: 'photopro-secret-key-dev-2026-secure';
    }

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

        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            $userId = $decoded->sub ?? null;

            if (!$userId) {
                return $this->unauthorizedResponse('Token sans identité (sub manquant)');
            }

            $request = $request->withAttribute('user_id', $userId);
        } catch (\Throwable $e) {
            return $this->unauthorizedResponse('Token invalide : ' . $e->getMessage());
        }

        return $handler->handle($request);
    }

    private function unauthorizedResponse(string $message): ResponseInterface
    {
        $response = new Response(401);
        $payload = ['error' => 'Unauthorized', 'message' => $message];
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }
}