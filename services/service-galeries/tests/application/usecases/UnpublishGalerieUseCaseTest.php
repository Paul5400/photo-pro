<?php

use PHPUnit\Framework\TestCase;
use app\src\application_core\application\usecases\UnpublishGalerieUseCase;
use app\src\application_core\application\ports\repositories\GalerieRepositoryInterface;

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