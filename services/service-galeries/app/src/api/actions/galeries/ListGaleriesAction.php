<?php
namespace photopro\galeries\api\actions\galeries;

use photopro\galeries\core\application\ports\repositories\GalerieRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ListGaleriesAction
{
    private GalerieRepositoryInterface $repo;

    public function __construct(GalerieRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $photographeId = $request->getAttribute('user_id');

        if (!$photographeId) {
            $response->getBody()->write(json_encode(['error' => 'Identité utilisateur manquante']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        $galeries = $this->repo->findByPhotographe($photographeId);

        $response->getBody()->write(json_encode($galeries));
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    }
}
