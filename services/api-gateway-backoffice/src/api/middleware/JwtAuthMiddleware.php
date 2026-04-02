<?php
declare(strict_types=1);

namespace photopro\backoffice\api\middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class JwtAuthMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');

        // On vérifie la présence et le format "Bearer <token>"
        if (empty($authHeader) || !preg_match('/^Bearer\s+(.+)$/i', $authHeader)) {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode([
                'error' => 'Unauthorized',
                'message' => 'Token JWT manquant ou format invalide (Authorization: Bearer <token> requis)'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }

        // On ne vérifie pas la signature (cela sera fait par le microservice Auth)
        return $handler->handle($request);
    }
}
