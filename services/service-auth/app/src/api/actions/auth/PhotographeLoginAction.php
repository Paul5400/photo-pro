<?php
declare(strict_types=1);

namespace photopro\auth\api\actions\auth;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use photopro\auth\core\application\dto\LoginDTO;
use photopro\auth\core\application\ports\api\AuthnServiceInterface;

class PhotographeLoginAction
{
    public function __construct(
        private AuthnServiceInterface $authService
    ) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $body = $request->getParsedBody();
        
        $dto = new LoginDTO(
            $body['email'] ?? '',
            $body['password'] ?? ''
        );
        
        try {
            $result = $this->authService->login($dto);
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
            $statusCode = $e->getCode();
            // On s'assure que le code HTTP est valide pour Slim (entre 400 et 599)
            if (!is_int($statusCode) || $statusCode < 400 || $statusCode > 549) {
                $statusCode = 500;
            }

            $response->getBody()->write(json_encode([
                'error' => $e->getMessage()
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($statusCode);
        }
    }
}
