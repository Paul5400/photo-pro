<?php
declare(strict_types=1);

namespace photopro\auth\api\actions\auth;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use photopro\auth\core\application\dto\CreatePhotographeDTO;
use photopro\auth\core\application\ports\api\AuthnServiceInterface;

class RegisterAction
{
    public function __construct(
        private AuthnServiceInterface $authService
    ) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $body = $request->getParsedBody();
        
        $dto = new CreatePhotographeDTO(
            $body['nom'] ?? '',
            $body['pseudo'] ?? '',
            $body['email'] ?? '',
            $body['password'] ?? '',
            !empty($body['telephone']) ? $body['telephone'] : null,
            !empty($body['description']) ? $body['description'] : null,
        );

        try {
            $result = $this->authService->register($dto);
            $cookie = sprintf(
                'refresh_token=%s; Path=/auth/refresh; Max-Age=%d; HttpOnly; SameSite=Lax',
                rawurlencode($result['refresh_token']),
                30 * 24 * 60 * 60
            );
            unset($result['refresh_token']);
            $response->getBody()->write(json_encode($result));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withAddedHeader('Set-Cookie', $cookie)
                ->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => $e->getMessage()
            ]));
            
            $statusCode = $e->getCode();
            if ($statusCode < 100 || $statusCode > 599) {
                $statusCode = 500;
            }
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($statusCode);
        }
    }
}
