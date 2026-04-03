<?php

namespace photopro\galeries\api\actions\galeries;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UnpublishGalerieAction
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
            $user = $request->getAttribute('user');
            $userId = $user->id ?? $request->getHeaderLine('X-User-Id');

            $this->useCase->execute($galleryId, $userId);

            $response->getBody()->write(json_encode([
                'message' => 'Galerie dépubliée avec succès'
            ]));

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