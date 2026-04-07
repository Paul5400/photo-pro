<?php

/**
 * Point d'entrée du service-galeries (Slim 4).
 *
 * Routes publiques (sans JWT) :
 *   GET  /galeries           - Liste les galeries (publiques ou toutes si X-User-Id fourni)
 *   GET  /galeries/{id}      - Détail d'une galerie publiée (code_acces requis si privée)
 *
 * Routes protégées (JWT obligatoire via AuthMiddleware) :
 *   POST   /galeries                      - Créer une galerie
 *   PATCH  /galeries/{id}/photos          - Ajouter une photo à une galerie
 *   DELETE /galeries/{id}/photos/{photoId}- Retirer une photo d'une galerie
 *   GET    /galeries/{id}/preview         - Aperçu complet (photographe propriétaire uniquement)
 *   POST   /galeries/{id}/publish         - Publier une galerie
 *   POST   /galeries/{id}/unpublish       - Dépublier une galerie (repasser en brouillon)
 *   POST   /photos/upload                 - Uploader une photo vers service-stockage
 */

use photopro\galeries\api\actions\galeries\CreateGalerieAction;
use photopro\galeries\api\actions\galeries\AddPhotoGalerieAction;
use photopro\galeries\api\actions\galeries\DeletePhotoFromGalerieAction;
use photopro\galeries\api\actions\galeries\GetGaleriesAction;
use photopro\galeries\api\actions\galeries\GetGalerieAction;
use photopro\galeries\api\actions\galeries\PreviewGalerieAction;
use photopro\galeries\api\actions\galeries\PublishGalerieAction;
use photopro\galeries\api\actions\galeries\UnpublishGalerieAction;
use photopro\galeries\api\actions\photos\UploadPhotoAction;
use photopro\galeries\api\middlewares\AuthMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/config/di/bootstrap.php';


$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

// Middleware CORS — autorise tous les domaines (à restreindre en production)
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// --- Routes publiques (pas de JWT requis) ---

// Liste les galeries publiques publiées, ou toutes les galeries d'un photographe si X-User-Id est fourni
$app->get('/galeries[/]', GetGaleriesAction::class);

// Détail d'une galerie publiée ; les galeries privées nécessitent ?code_acces=XXX
$app->get('/galeries/{id}[/]', GetGalerieAction::class);

// Sanity-check
$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Hello from service-galeries API");
    return $response;
});

// --- Routes protégées (JWT HS256 vérifié par AuthMiddleware) ---

// Créer une nouvelle galerie (photographe authentifié)
$app->post('/galeries', CreateGalerieAction::class)
    ->add(new AuthMiddleware());

// Ajouter une photo (déjà uploadée) à une galerie existante
$app->patch('/galeries/{id}/photos', AddPhotoGalerieAction::class)
    ->add(new AuthMiddleware());

// Retirer une photo d'une galerie
$app->delete('/galeries/{id}/photos/{photoId}', DeletePhotoFromGalerieAction::class)
    ->add(new AuthMiddleware());

// Aperçu complet d'une galerie avec URLs pré-signées S3 (réservé au photographe propriétaire)
$app->get('/galeries/{id}/preview', PreviewGalerieAction::class)
    ->add(new AuthMiddleware());

// Publier une galerie (statut brouillon → publie) ; la galerie doit contenir au moins une photo
$app->post('/galeries/{id}/publish', PublishGalerieAction::class)
    ->add(new AuthMiddleware());

// Dépublier une galerie (statut publie → brouillon)
$app->post('/galeries/{id}/unpublish', UnpublishGalerieAction::class)
    ->add(new AuthMiddleware());


$app->run();
