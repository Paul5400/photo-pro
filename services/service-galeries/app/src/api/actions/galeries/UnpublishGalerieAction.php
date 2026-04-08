<?php

namespace photopro\galeries\api\actions\galeries;

use photopro\galeries\api\traits\JwtDecoderTrait;
use photopro\galeries\core\application\ports\repositories\GalerieRepositoryInterface;
use photopro\galeries\infra\messaging\RabbitMQEventPublisher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Action POST /galeries/{id}/unpublish
 *
 * Dépublie une galerie appartenant au photographe authentifié.
 * Repasse la galerie en statut "brouillon" et efface la date de publication.
 * Publie un événement RabbitMQ gallery.unpublished pour notifier le client.
 *
 * Réponses :
 *   200 - Galerie dépubliée avec succès
 *   400 - Galerie non trouvée ou n'appartenant pas à l'utilisateur
 *   401 - Token manquant ou invalide
 */
class UnpublishGalerieAction
{
    use JwtDecoderTrait;
    public function __construct(
        private GalerieRepositoryInterface $galerieRepository,
        private RabbitMQEventPublisher $publisher,
    ) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $galleryId = $args['id'];

            $authHeader = $request->getHeaderLine('Authorization');
            $jwtToken   = str_starts_with($authHeader, 'Bearer ') ? substr($authHeader, 7) : '';
            $userId     = $this->extractUserIdFromJwt($jwtToken);

            if (!$userId) {
                $response->getBody()->write(json_encode(['error' => 'Token invalide']));
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }

            $data = $this->galerieRepository->getGalerieDataForNotification($galleryId);

            $this->galerieRepository->unpublishGallery($galleryId, $userId);

            if ($data && !empty($data['email_client'])) {
                try {
                    $this->publisher->publish('gallery.unpublished', [
                        'galerie_id'    => $galleryId,
                        'galerie_titre' => $data['titre'],
                        'client_email'  => $data['email_client'] ?? '',
                        'code_acces'    => $data['code_acces'] ?? '',
                        'url_acces'     => $data['url_acces'] ?? '',
                    ]);
                } catch (\Throwable $e) {
                    error_log('[UnpublishGalerieAction] RabbitMQ publish failed: ' . $e->getMessage());
                }
            }

            $response->getBody()->write(json_encode(['message' => 'Galerie dépubliée avec succès']));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (\Throwable $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }
}
