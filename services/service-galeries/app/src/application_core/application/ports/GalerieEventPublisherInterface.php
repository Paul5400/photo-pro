<?php
declare(strict_types=1);

namespace photopro\galeries\core\application\ports;

interface GalerieEventPublisherInterface
{
    /**
     * Publie un événement galerie vers le bus de messages.
     *
     * @param string $routingKey  Ex. 'gallery.published', 'gallery.unpublished'
     * @param array  $payload     Données de l'événement
     */
    public function publish(string $routingKey, array $payload): void;
}
