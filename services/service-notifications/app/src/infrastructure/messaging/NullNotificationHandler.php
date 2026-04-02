<?php
declare(strict_types=1);

namespace photopro\notifications\infra\messaging;

use photopro\notifications\core\application\ports\NotificationHandlerInterface;
class NullNotificationHandler implements NotificationHandlerInterface
{
    public function handle(array $event): void
    {
        $type    = $event['type_event']    ?? '?';
        $galerie = $event['galerie_titre'] ?? '?';
        $email   = $event['client_email']  ?? '?';

        echo sprintf(
            "[NullHandler] Événement reçu : %s | galerie=%s | dest=%s\n",
            $type,
            $galerie,
            $email
        );
    }
}
