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
        try {
            $payload = $request->getBody()->getContents();
            $data = json_decode($payload, true);
            $userId = $request->getAttribute('user_id');
            if ($userId === null) {
                throw new \InvalidArgumentException('ID de photographe manquant dans la requête.');
            }

            if (!is_array($data)) {
                throw new \InvalidArgumentException('Corps de requête JSON invalide.');
            }

            $galerieDTO = new GalerieDTO(
                $data['titre'] ?? '',
                $data['type'] ?? '',
                $data['mode_mise_en_page'] ?? '',
                $data['statut'] ?? '',
                new \DateTime(),
                $userId ,
                $data['description'] ?? null,
                $data['photo_couverture_id'] ?? null,
                isset($data['published_at']) ? new \DateTime($data['published_at']) : null,
                $data['nomClient'] ?? null,
                $data['emailClient'] ?? null,
                $data['telephoneClient'] ?? null
                
            );

            $galerieDTO = $this->galerieService->createGalerie($galerieDTO);

            $response->getBody()->write(json_encode([
                'galerie' => $galerieDTO,
                'message' => 'Galerie créée avec succès'
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\InvalidArgumentException | \TypeError $exception) {
            $response->getBody()->write(json_encode([
                'error' => 'Requête invalide',
                'message' => $exception->getMessage(),
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        } catch (\Throwable $exception) {
            $response->getBody()->write(json_encode([
                'error' => 'Erreur serveur',
                'message' => $exception->getMessage(),
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}