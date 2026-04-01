<?php
declare(strict_types=1);

namespace photopro\notifications\core\application\ports;

use photopro\notifications\core\application\dto\NotificationEventDTO;

 // Port sortant, contrat d'envoi de mail.
interface MailerInterface
{
    /**
     * Envoie un ou plusieurs mails selon le type d'événement.
     *
     * @throws \RuntimeException si l'envoi échoue
     */
    public function send(NotificationEventDTO $event): void;
}
