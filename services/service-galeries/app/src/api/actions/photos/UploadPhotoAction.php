<?php

namespace photopro\galeries\api\actions\photos;

use photopro\galeries\core\application\ports\repositories\PhotoRepositoryInterface;
use photopro\galeries\core\application\ports\services\StorageClientInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Action POST /photos/upload
 *
 * Upload une image vers service-stockage (S3 via SeaweedFS) puis sauvegarde
 * une projection locale de la photo dans la base gallery_db (table `photo`).
 * Cette projection locale permet de réaliser des JOIN entre galeries et photos
 * sans inter-service à chaque requête.
 *
 * Flux :
 *   1. Récupère le fichier depuis le multipart (champ "image")
 *   2. Propage le JWT vers service-stockage (autorisation)
 *   3. service-stockage génère un UUID et stocke le fichier dans S3
 *   4. Insère la projection (id + chemin_s3) dans gallery_db
 *
 * Corps multipart/form-data attendu :
 *   - image  : fichier image
 *   - titre  : libellé lisible (optionnel)
 *
 * Réponses :
 *   201 - { photo_id, chemin_s3 }
 *   400 - Fichier manquant
 *   422 - Paramètres invalides
 *   502 - Réponse invalide de service-stockage
 *   500 - Erreur serveur
 */
class UploadPhotoAction
{
    public function __construct(
        private StorageClientInterface  $storageClient,
        private PhotoRepositoryInterface $photoRepository
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        try {
            $uploadedFiles = $request->getUploadedFiles();

            if (empty($uploadedFiles['image'])) {
                $response->getBody()->write(json_encode(['error' => 'Aucun fichier image fourni (champ "image" attendu)']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $file     = $uploadedFiles['image'];
            $parsedBody = $request->getParsedBody();
            $titre    = $parsedBody['titre'] ?? null;

            // Extraire le JWT de la requête entrante pour le propager vers service-stockage
            $authHeader = $request->getHeaderLine('Authorization');
            $jwtToken   = null;
            if (str_starts_with($authHeader, 'Bearer ')) {
                $jwtToken = substr($authHeader, 7);
            }

            // Appel vers service-stockage (upload S3 + INSERT stockage.db)
            $stream = $file->getStream();
            $stream->rewind();
            $result = $this->storageClient->upload(
                (string) $stream,
                $file->getClientFilename(),
                $file->getClientMediaType(),
                $titre,
                $jwtToken
            );

            $photoId  = $result['photo_id'] ?? null;
            $cheminS3 = $result['path']     ?? null;

            if (!$photoId || !$cheminS3) {
                $response->getBody()->write(json_encode(['error' => 'Réponse invalide du service de stockage']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(502);
            }

            // INSERT projection locale dans gallery.db
            $this->photoRepository->save($photoId, $cheminS3, $titre);

            $response->getBody()->write(json_encode([
                'photo_id'  => $photoId,
                'chemin_s3' => $cheminS3,
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

        } catch (\InvalidArgumentException | \TypeError $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
        } catch (\Throwable $e) {
            $response->getBody()->write(json_encode([
                'error'   => 'Erreur serveur',
                'message' => $e->getMessage(),
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
