<?php
declare(strict_types=1);

namespace photopro\backoffice\api\middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class JwtAuthMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader) || !preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return $this->unauthorized('Token JWT manquant ou format invalide (Authorization: Bearer <token> requis)');
        }

        $token  = $matches[1];
        $secret = getenv('JWT_SECRET') ?: 'photopro-secret-key-dev-2026-secure';

        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
        } catch (ExpiredException $e) {
            return $this->unauthorized('Token expiré');
        } catch (SignatureInvalidException $e) {
            return $this->unauthorized('Signature JWT invalide');
        } catch (\Exception $e) {
            return $this->unauthorized('Token JWT invalide');
        }

        $userId = $decoded->sub ?? null;
        if (!$userId) {
            return $this->unauthorized('Token JWT invalide : claim sub manquant');
        }

        $request = $request->withAttribute('user_id', $userId);

        return $handler->handle($request);
    }

    private function unauthorized(string $message): Response
    {
        $response = new SlimResponse();
        $response->getBody()->write(json_encode([
            'error'   => 'Unauthorized',
            'message' => $message,
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401);
    }
}
