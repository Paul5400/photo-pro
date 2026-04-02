<?php

namespace photopro\auth\api\actions\auth;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use photopro\auth\core\application\ports\api\PhotographeServiceInterface;

class GetAllPhotographesAction
{
    private PhotographeServiceInterface $service;

    public function __construct(PhotographeServiceInterface $service)
    {
        $this->service = $service;
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $photographes = $this->service->getAllPhotographes();

        $data = array_map(fn($p) => [
            'id'     => $p->id,
            'nom'    => $p->nom,
            'pseudo' => $p->pseudo,
            'email'  => $p->email,
        ], $photographes);

        $response->getBody()->write(json_encode($data));

        return $response->withHeader('Content-Type', 'application/json');
    }
}