<?php
namespace photopro\galeries\api\actions\galeries;

use DateTime;
use Ramsey\Uuid\Uuid;
use photopro\galeries\core\application\ports\services\GalerieServiceInterface;
use photopro\galeries\core\domain\entities\GaleriePhoto;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Action PATCH /galeries/{id}/photos
 *
 * Associe une photo (déjà uploadée via service-stockage) à une galerie.
 * La vérification de propriété est assurée par la couche repository.
 *
 * Corps JSON attendu :
 *   - photo_id (UUID de la photo dans service-stockage)
 *   - ordre    (int, position d'affichage, défaut 0)
 *
 * Réponses :
 *   200 - Photo associée avec succès
 *   400 - Corps JSON invalide ou paramètres manquants
 *   422 - UUID invalide
 *   500 - Erreur serveur (ex : galerie n'appartenant pas à l'utilisateur)
 */
class AddPhotoGalerieAction
{
    private GalerieServiceInterface $galerieService;

    public function __construct(GalerieServiceInterface $galerieService)
    {
        $this->galerieService = $galerieService;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $galerieId = $args['id'] ?? $args['galerieId'] ?? null;
        $data = $request->getParsedBody();

        if (!is_array($data)) {
            $raw = (string) $request->getBody();
            $data = json_decode($raw, true);
        }

        if (!is_array($data)) {
            $response->getBody()->write(json_encode(['error' => 'Requête JSON invalide']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        if (empty($galerieId) || empty($data['photo_id'])) {
            $response->getBody()->write(json_encode(['error' => 'galerie id et photo_id sont requis']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $galeriePhoto = new GaleriePhoto(
                Uuid::fromString($galerieId),
                Uuid::fromString($data['photo_id']),
                $data['ordre'] ?? 0,
                new DateTime()
            );

            $this->galerieService->addPhotoToGalerie($galeriePhoto);

            $response->getBody()->write(json_encode(['message' => 'Photo ajoutée à la galerie avec succès']));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        } catch (\InvalidArgumentException | \TypeError $exception) {
            $response->getBody()->write(json_encode([
                'error' => 'Requête invalide',
                'message' => $exception->getMessage(),
            ]));
            return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
        } catch (\Throwable $exception) {
            $response->getBody()->write(json_encode([
                'error' => 'Échec de l’ajout de la photo',
                'message' => $exception->getMessage(),
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}