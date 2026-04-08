<?php

namespace photopro\galeries\api\middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Middleware d'authentification JWT (HS256).
 *
 * Vérifie la présence et la validité du token Bearer dans l'en-tête Authorization.
 * Si le token est valide, l'identifiant utilisateur (claim "sub") est injecté
 * dans l'attribut "user_id" de la requête pour être consommé par les actions.
 *
 * En cas d'échec, renvoie une réponse 401 JSON avec un message d'erreur explicite.
 */
class AuthMiddleware
{
    private string $secret;

    public function __construct()
    {
        // Le secret JWT est lu depuis la variable d'environnement JWT_SECRET
        // (définie dans docker-compose.yml), avec un fallback pour le développement local.
        $this->secret = getenv('JWT_SECRET') ?: 'photopro-secret-key-dev-2026-secure';
    }

    public function __invoke(Request $request, RequestHandler $handler): ResponseInterface
    {
        $authorizationHeader = $request->getHeaderLine('Authorization');

        // Vérifie la présence de l'en-tête Authorization
        if (!$authorizationHeader) {
            return $this->unauthorizedResponse('Token manquant');
        }

        // Vérifie le format "Bearer <token>"
        if (!preg_match('/^Bearer\s+(.+)$/i', $authorizationHeader, $matches)) {
            return $this->unauthorizedResponse('Format du token invalide');
        }

        $token = $matches[1];

        try {
            // Décode et valide la signature HS256
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));

            // L'identifiant utilisateur est dans le claim "sub"
            $userId = $decoded->sub;

            // Propage l'identifiant aux actions via les attributs de la requête
            $request = $request->withAttribute('user_id', $userId);

        } catch (\Exception $e) {
            return $this->unauthorizedResponse('Token invalide');
        }

        return $handler->handle($request);
    }

    /**
     * Construit une réponse 401 JSON standardisée.
     */
    private function unauthorizedResponse(string $message): ResponseInterface
    {
        $response = new Response(401);
        $payload = ['error' => 'Unauthorized', 'message' => $message];
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
