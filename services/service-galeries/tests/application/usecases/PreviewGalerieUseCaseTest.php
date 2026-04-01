<?php

use PHPUnit\Framework\TestCase;
use app\src\application_core\application\usecases\PreviewGalerieUseCase;
use app\src\application_core\application\ports\repositories\GalerieRepositoryInterface;

class PreviewGalerieUseCaseTest extends TestCase
{
    public function testPreviewReturnsMappedGallery()
    {
        $repo = $this->createMock(GalerieRepositoryInterface::class);

        $repo->method('getGalleryPreview')
             ->willReturn([
                 [
                     'galerie_id' => 'gal1',
                     'titre' => 'Test',
                     'description' => 'desc',
                     'type' => 'private',
                     'statut' => 'draft',
                     'mode_mise_en_page' => 'grid',
                     'created_at' => '2024-01-01',
                     'published_at' => null,
                     'photo_id' => 'photo1',
                     'url' => 'url1',
                     'photo_titre' => 'Photo 1'
                 ]
             ]);

        $useCase = new PreviewGalerieUseCase($repo);

        $result = $useCase->execute('gal1', 'user1');

        $this->assertNotNull($result);
        $this->assertEquals('gal1', $result['id']);
        $this->assertCount(1, $result['photos']);
    }

    public function testPreviewReturnsNullIfNotFound()
    {
        $repo = $this->createMock(GalerieRepositoryInterface::class);

        $repo->method('getGalleryPreview')
            ->willReturn([]);

        $useCase = new PreviewGalerieUseCase($repo);

        $result = $useCase->execute('fake', 'user');

        $this->assertNull($result);
    }
}