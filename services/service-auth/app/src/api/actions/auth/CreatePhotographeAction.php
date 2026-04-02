<?php

namespace photopro\auth\api\actions\auth;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use photopro\auth\core\application\dto\CreatePhotographeDTO;
use photopro\auth\core\application\ports\api\PhotographeServiceInterface;

class CreatePhotographeAction
{
    private PhotographeServiceInterface $service;

    public function __construct(PhotographeServiceInterface $service)
    {
        $this->service = $service;
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();

        $dto = new CreatePhotographeDTO(
            $body['nom'] ?? '',
            $body['pseudo'] ?? '',
            $body['email'] ?? '',
            $body['password'] ?? '',
            $body['telephone'] ?? null,
            $body['description'] ?? null
        );

        $photographe = $this->service->createPhotographe($dto);

        $response->getBody()->write(json_encode([
            'id'          => $photographe->id,
            'nom'         => $photographe->nom,
            'pseudo'      => $photographe->pseudo,
            'email'       => $photographe->email,
            'password'    => $photographe->password,
            'telephone'   => $photographe->telephone,
            'description' => $photographe->description,
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }
}