<?php

use PHPUnit\Framework\TestCase;
use photopro\galeries\core\application\usecases\UnpublishGalerieUseCase;
use photopro\galeries\core\application\ports\repositories\GalerieRepositoryInterface;

require_once __DIR__ . '/../../../app/src/application_core/application/ports/repositories/GalerieRepositoryInterface.php';
require_once __DIR__ . '/../../../app/src/application_core/application/usecases/UnpublishGalerieUseCase.php';

class UnpublishGalerieUseCaseTest extends TestCase
{
    public function testUnpublishGalleryCallsRepository()
    {
        $repo = $this->createMock(GalerieRepositoryInterface::class);

        $repo->expects($this->once())
             ->method('unpublishGallery')
             ->with('galerie_id', 'user_id');

        $useCase = new UnpublishGalerieUseCase($repo);

        $useCase->execute('galerie_id', 'user_id');
    }
}