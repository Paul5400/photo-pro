<?php

namespace storage\api\middlewares;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Exception\HttpUnauthorizedException;

/**
 * Middleware de validation du JWT pour le service de stockage
 */
class JwtMiddleware implements MiddlewareInterface
{
    private string $secret;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    public function process(Request $request, Handler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            throw new HttpUnauthorizedException($request, "Token manquant ou format invalide.");
        }

        $token = $matches[1];

        try {
            // Décodage du JWT avec la même clé secrète que le service Auth
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            
            // On convertit TOUT en tableau récursif pour éviter les erreurs d'accès Objet/Tableau
            $decodedArray = json_decode(json_encode($decoded), true);

            // On ajoute les informations de l'utilisateur dans les attributs de la requête pour l'Action
            $request = $request->withAttribute('user', $decodedArray);

            return $handler->handle($request);
        } catch (\Exception $e) {
            throw new HttpUnauthorizedException($request, "Token invalide ou expiré : " . $e->getMessage());
        }
    }
}
