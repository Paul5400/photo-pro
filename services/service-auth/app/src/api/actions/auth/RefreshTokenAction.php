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
        $cookies = $request->getCookieParams();
        $refreshToken = $cookies['refresh_token'] ?? '';
        if ($refreshToken === '') {
            $body = $request->getParsedBody();
            $refreshToken = $body['refresh_token'] ?? '';
        }

        if (empty($refreshToken)) {
            $response->getBody()->write(json_encode([
                'error' => 'Refresh token is required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $result = $this->authService->refreshToken($refreshToken);
            $cookie = sprintf(
                'refresh_token=%s; Path=/auth/refresh; Max-Age=%d; HttpOnly; SameSite=Lax',
                rawurlencode($result['refresh_token']),
                30 * 24 * 60 * 60
            );
            unset($result['refresh_token']);
            $response->getBody()->write(json_encode($result));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withAddedHeader('Set-Cookie', $cookie);
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
