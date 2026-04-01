<?php

use PHPUnit\Framework\TestCase;
use app\src\application_core\application\usecases\PublishGalerieUseCase;
use app\src\application_core\application\ports\repositories\GalerieRepositoryInterface;

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