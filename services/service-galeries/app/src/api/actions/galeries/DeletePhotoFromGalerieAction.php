<?php
namespace photopro\galeries\api\actions\galeries;

use photopro\galeries\core\application\ports\repositories\GalerieRepositoryInterface;
use photopro\galeries\core\application\ports\services\GalerieServiceInterface;
use photopro\galeries\infra\messaging\RabbitMQEventPublisher;

/**
 * Action DELETE /galeries/{id}/photos/{photoId}
 *
 * Retire une photo d'une galerie (supprime l'association galerie_photo).
 * Ne supprime pas la photo dans service-stockage/S3.
 * Publie un événement gallery.modified si la galerie est privée et publiée.
 *
 * Réponses :
 *   200 - Photo retirée avec succès
 *   400 - Identifiants manquants
 *   500 - Erreur serveur
 */
class DeletePhotoFromGalerieAction
{
    public function __construct(
        private GalerieServiceInterface    $galerieService,
        private GalerieRepositoryInterface $galerieRepository,
        private RabbitMQEventPublisher     $publisher,
    ) {}

    public function __invoke($request, $response, $args)
    {
        $galerieId = $args['id'] ?? $args['galerieId'] ?? null;
        $photoId = $args['photoId'] ?? null;

        if (empty($galerieId) || empty($photoId)) {
            $response->getBody()->write(json_encode(['error' => 'galerie id and photoId are required']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $galerieData = $this->galerieRepository->getGalerieDataForNotification($galerieId);

            $this->galerieService->deletePhotoFromGalerie($galerieId, $photoId);

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
                    error_log('[DeletePhotoFromGalerieAction] RabbitMQ publish failed: ' . $e->getMessage());
                }
            }

            $response->getBody()->write(json_encode(['message' => 'Photo deleted from galerie successfully']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Failed to delete photo from galerie', 'details' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}

