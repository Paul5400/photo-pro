<?php

namespace photopro\auth\api\actions\auth;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use photopro\auth\core\application\ports\api\PhotographeServiceInterface;

class GetPhotographeByIdAction
{
    private PhotographeServiceInterface $service;

    public function __construct(PhotographeServiceInterface $service)
    {
        $this->service = $service;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $photographe = $this->service->getPhotographeById($args['id']);

        if ($photographe === null) {
            $response->getBody()->write(json_encode(['error' => 'Photographe non trouvé']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $response->getBody()->write(json_encode([
            'id'          => $photographe->id,
            'nom'         => $photographe->nom,
            'pseudo'      => $photographe->pseudo,
            'email'       => $photographe->email,
            'password'    => $photographe->password,
            'telephone'   => $photographe->telephone,
            'description' => $photographe->description,
            'created_at'  => $photographe->created_at,
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}