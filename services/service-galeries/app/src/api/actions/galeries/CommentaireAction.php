<?php

namespace photopro\galeries\api\actions\galeries;

use photopro\galeries\core\application\usecases\AjouterCommentaireUseCase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Action publique (sans JWT) : ajouter un commentaire à une photo d'une galerie.
 *
 * Route : POST /galeries/{id}/photos/{photoId}/commentaires
 *
 * Corps JSON attendu :
 *   {
 *     "auteur":     "Prénom Nom",
 *     "contenu":    "Texte du commentaire",
 *     "code_acces": "XXXXXX"   // requis uniquement pour les galeries privées
 *   }
 *
 * Réponses :
 *   201 {"id": "<uuid>", "message": "Commentaire ajouté avec succès"}
 *   400 champ auteur ou contenu vide
 *   403 galerie non publiée ou code_acces invalide
 *   404 galerie introuvable
 */
class CommentaireAction
{
    public function __construct(private AjouterCommentaireUseCase $useCase) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $galerieId = $args['id'];
        $photoId   = $args['photoId'];

        $body      = (array) $request->getParsedBody();
        $auteur    = trim((string) ($body['auteur']    ?? ''));
        $contenu   = trim((string) ($body['contenu']   ?? ''));
        $codeAcces = isset($body['code_acces']) ? (string) $body['code_acces'] : null;

        try {
            $commentaireId = $this->useCase->execute($galerieId, $photoId, $auteur, $contenu, $codeAcces);
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } catch (\DomainException $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        } catch (\RuntimeException $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $response->getBody()->write(json_encode([
            'id'      => $commentaireId,
            'message' => 'Commentaire ajouté avec succès',
        ]));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }
}
