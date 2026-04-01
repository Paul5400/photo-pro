<?php
namespace photopro\galeries\api\actions\galeries;
use photopro\galeries\core\application\dto\GalerieDTO;
use photopro\galeries\core\application\ports\services\GalerieServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CreateGalerieAction
{
    private GalerieServiceInterface $galerieService;

    public function __construct(GalerieServiceInterface $galerieService)
    {
        $this->galerieService = $galerieService;
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);

        // Validation des données d'entrée
        if (empty($data['titre']) || empty($data['type']) || empty($data['mode_mise_en_page']) || empty($data['statut']) || empty($data['photographe_id'])) {
            $response->getBody()->write(json_encode(['error' => 'Missing required fields']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Création du DTO
        $galerieDTO = new GalerieDTO(
            $data['titre'],
            $data['type'],
            $data['mode_mise_en_page'],
            $data['statut'],
            new \DateTime(),
            $data['photographe_id'],
            $data['description'] ?? null,
            $data['photo_couverture_id'] ?? null,
            isset($data['published_at']) ? new \DateTime($data['published_at']) : null
        );

        try {
            $galerie = $this->galerieService->createGalerie($galerieDTO);
            $response->getBody()->write(json_encode(['message' => 'Galerie created successfully', 'galerie_id' => $galerie->getId()]));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Failed to create galerie', 'details' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}