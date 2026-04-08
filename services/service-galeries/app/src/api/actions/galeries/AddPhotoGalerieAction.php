<?php
namespace photopro\galeries\api\actions\galeries;

use DateTime;
use Ramsey\Uuid\Uuid;
use photopro\galeries\core\application\ports\repositories\GalerieRepositoryInterface;
use photopro\galeries\core\application\ports\services\GalerieServiceInterface;
use photopro\galeries\core\domain\entities\GaleriePhoto;
use photopro\galeries\infra\messaging\RabbitMQEventPublisher;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Action PATCH /galeries/{id}/photos
 *
 * Associe une photo (éjà uploadée via service-stockage) à une galerie.
 * La vérification de propriété est assurée par la couche repository.
 * Publie un événement gallery.modified si la galerie est privée et publiée.
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
    public function __construct(
        private GalerieServiceInterface    $galerieService,
        private GalerieRepositoryInterface $galerieRepository,
        private RabbitMQEventPublisher     $publisher,
    ) {}

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

            $galerieData = $this->galerieRepository->getGalerieDataForNotification($galerieId);
            if ($galerieData && $galerieData['statut'] === 'publie' && !empty($galerieData['email_client'])) {
                try {
                    $this->publisher->publish('gallery.modified', [
                        'galerie_id'    => $galerieId,
                        'galerie_titre' => $galerieData['titre'],
                        'client_email'  => $galerieData['email_client'],
                        'code_acces'    => $galerieData['code_acces'] ?? '',
                        'url_acces'     => $galerieData['url_acces'] ?? '',
                    ]);
                } catch (\Throwable $e) {
                    error_log('[AddPhotoGalerieAction] RabbitMQ publish failed: ' . $e->getMessage());
                }
            }

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
