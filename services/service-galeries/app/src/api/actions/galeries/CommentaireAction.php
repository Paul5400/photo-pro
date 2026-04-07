<?php
namespace photopro\galeries\api\actions\galeries;

use photopro\galeries\core\application\usecases\AjouterCommentaireUseCase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CommentaireAction
{
    private AjouterCommentaireUseCase $useCase;

    public function __construct(AjouterCommentaireUseCase $useCase)
    {
        $this->useCase = $useCase;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $galerieId = $args['id']      ?? null;
        $photoId   = $args['photoId'] ?? null;

        if (empty($galerieId) || empty($photoId)) {
            $response->getBody()->write(json_encode(['error' => 'Paramètres manquants']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $data    = $request->getParsedBody();
        $contenu = trim($data['contenu'] ?? '');
        $auteur  = !empty($data['auteur']) ? trim($data['auteur']) : null;
        $code    = $data['code_acces'] ?? null;

        if ($contenu === '') {
            $response->getBody()->write(json_encode(['error' => 'Le champ contenu est obligatoire']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $id = $this->useCase->execute($galerieId, $photoId, $auteur, $contenu, $code);

            $response->getBody()->write(json_encode([
                'id'      => $id,
                'message' => 'Commentaire ajouté avec succès',
            ]));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
        } catch (\InvalidArgumentException $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        } catch (\RuntimeException $e) {
            $msg = $e->getMessage();
            $status = str_contains($msg, 'non trouvée') || str_contains($msg, 'non trouvé') ? 404 : 403;
            $response->getBody()->write(json_encode(['error' => $msg]));
            return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
        } catch (\Throwable $e) {
            $response->getBody()->write(json_encode(['error' => 'Erreur interne du serveur']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
