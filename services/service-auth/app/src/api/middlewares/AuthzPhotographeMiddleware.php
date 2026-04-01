<?php

declare(strict_types=1);

namespace photopro\auth\api\middlewares;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class AuthzPhotographeMiddleware
{
    public function __invoke(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $role = $request->getAttribute('role');

        if ($role !== 'photographe') {
            $response = new Response();
            $response->getBody()->write(json_encode([
                'error' => 403,
                'message' => 'Réservé aux photographes',
            ]));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        return $handler->handle($request);
    }
}

