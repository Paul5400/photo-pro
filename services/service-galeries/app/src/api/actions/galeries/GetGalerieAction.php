<?php
namespace photopro\galeries\api\actions\galeries;

use photopro\galeries\core\application\ports\repositories\GalerieRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class GetGalerieAction
{
    private GalerieRepositoryInterface $repo;

    public function __construct(GalerieRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $galerieId  = $args['id'] ?? null;
        $params     = $request->getQueryParams();
        $codeAcces  = $params['code_acces'] ?? null;

        if (empty($galerieId)) {
            $response->getBody()->write(json_encode(['error' => 'Identifiant de galerie manquant']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $result = $this->repo->findPublishedById($galerieId, $codeAcces);

            $response->getBody()->write(json_encode($result));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        } catch (\RuntimeException $e) {
            $msg    = $e->getMessage();
            $status = str_contains($msg, "Code d'accès") ? 403 : 404;

            $response->getBody()->write(json_encode(['error' => $msg]));
            return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
        } catch (\Throwable $e) {
            $response->getBody()->write(json_encode(['error' => 'Erreur interne du serveur']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
