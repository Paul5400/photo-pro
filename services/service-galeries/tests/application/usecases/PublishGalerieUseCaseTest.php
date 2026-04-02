<?php

use PHPUnit\Framework\TestCase;
use photopro\galeries\core\application\usecases\PublishGalerieUseCase;
use photopro\galeries\core\application\ports\repositories\GalerieRepositoryInterface;

require_once __DIR__ . '/../../../app/src/application_core/application/ports/repositories/GalerieRepositoryInterface.php';
require_once __DIR__ . '/../../../app/src/application_core/application/usecases/PublishGalerieUseCase.php';

class PublishGalerieUseCaseTest extends TestCase
{
    public function testPublishGalleryCallsRepository()
    {
        $repo = $this->createMock(GalerieRepositoryInterface::class);

        $repo->expects($this->once())
             ->method('publishGallery')
             ->with('galerie_id', 'user_id');

        $useCase = new PublishGalerieUseCase($repo);

        $useCase->execute('galerie_id', 'user_id');
    }
}