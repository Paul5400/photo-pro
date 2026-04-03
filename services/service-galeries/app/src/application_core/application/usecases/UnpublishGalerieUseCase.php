<?php

namespace photopro\galeries\core\application\usecases;

use photopro\galeries\core\application\ports\repositories\GalerieRepositoryInterface;
use photopro\galeries\core\application\ports\GalerieEventPublisherInterface;

class UnpublishGalerieUseCase
{
    public function __construct(
        private GalerieRepositoryInterface $repo,
        private ?GalerieEventPublisherInterface $publisher = null
    ) {}

    public function execute(string $galleryId, string $userId): void
    {
        $this->repo->unpublishGallery($galleryId, $userId);

        if ($this->publisher !== null) {
            try {
                $data = $this->repo->getGalerieForNotification($galleryId);
                // Seulement les galeries privées ont un client_email
                if (!empty($data['email_client'])) {
                    $this->publisher->publish('gallery.unpublished', [
                        'type_event'    => 'gallery.unpublished',
                        'galerie_id'    => $galleryId,
                        'galerie_titre' => $data['titre'] ?? '',
                        'client_email'  => $data['email_client'],
                        'url_acces'     => $data['url_acces'] ?? '',
                        'code_acces'    => $data['code_acces'] ?? '',
                        'date_event'    => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                    ]);
                }
            } catch (\Throwable) {
                // La notification est non-critique : on n'interrompt pas la dépublication
            }
        }
    }
}