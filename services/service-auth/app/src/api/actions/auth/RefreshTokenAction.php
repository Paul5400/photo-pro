<?php

declare(strict_types=1);

namespace photopro\auth\api\actions\auth;

use photopro\auth\core\application\ports\api\AuthnServiceInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RefreshTokenAction
{
    public function __construct(
        private AuthnServiceInterface $authService
    ) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = $request->getParsedBody();
        $refreshToken = $body['refresh_token'] ?? '';

        if (empty($refreshToken)) {
            $response->getBody()->write(json_encode([
                'error' => 'Refresh token is required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $result = $this->authService->refreshToken($refreshToken);
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => $e->getMessage()
            ]));
            return $response
                ->withStatus($e->getCode() === 401 ? 401 : 500)
                ->withHeader('Content-Type', 'application/json');
        }
    }
}
