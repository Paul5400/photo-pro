<?php

declare(strict_types=1);

namespace photopro\auth\api\actions\auth;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use photopro\auth\core\application\dto\ConnexionVisiteurDTO;
use photopro\auth\core\application\ports\api\AuthnServiceInterface;

class ConnexionVisiteurAction
{
    public function __construct(
        private AuthnServiceInterface $authService
    ) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $body = $request->getParsedBody();

        $dto = new ConnexionVisiteurDTO(
            $body['url_acces'] ?? '',
            $body['code_acces'] ?? ''
        );

        try {
            $result = $this->authService->loginVisiteur($dto);
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
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
