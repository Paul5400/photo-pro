<?php

namespace photopro\galeries\api\actions\galeries;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PreviewGalerieAction
{
    private $useCase;

    public function __construct($useCase)
    {
        $this->useCase = $useCase;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args)
    {
        try {
            $galleryId = $args['id'];
            $userId = $request->getAttribute('user_id');

            if (!$userId) {
                $response->getBody()->write(json_encode(['error' => 'Identité utilisateur manquante']));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }

            $gallery = $this->useCase->execute($galleryId, $userId);

            if (!$gallery) {
                $response->getBody()->write(json_encode([
                    'error' => 'Galerie non trouvée'
                ]));

                return $response->withStatus(404)
                                ->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode($gallery));

            return $response->withStatus(200)
                            ->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {

            $response->getBody()->write(json_encode([
                'error' => $e->getMessage()
            ]));

            return $response->withStatus(400)
                            ->withHeader('Content-Type', 'application/json');
        }
    }
}