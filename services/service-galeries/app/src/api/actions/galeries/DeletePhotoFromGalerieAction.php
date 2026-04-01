<?php
namespace photopro\galeries\api\actions\galeries;
use photopro\galeries\core\application\ports\services\GalerieServiceInterface;

class DeletePhotoFromGalerieAction
{
    private GalerieServiceInterface $galerieService;

    public function __construct(GalerieServiceInterface $galerieService)
    {
        $this->galerieService = $galerieService;
    }

    public function __invoke($request, $response, $args)
    {
        $galerieId = $args['galerieId'];
        $photoId = $args['photoId'];

        try {
            $this->galerieService->deletePhotoFromGalerie($galerieId, $photoId);
            $response->getBody()->write(json_encode(['message' => 'Photo deleted from galerie successfully']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Failed to delete photo from galerie', 'details' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}