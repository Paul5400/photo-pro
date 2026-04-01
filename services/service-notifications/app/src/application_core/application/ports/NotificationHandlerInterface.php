<?php
declare(strict_types=1);

namespace photopro\notifications\core\application\ports;

/**
 * Port entrant, définit le contrat que le consumer AMQP appelle.
 * L'implémentation est le usecase EnvoyerNotificationUseCase .
 */
interface NotificationHandlerInterface
{
    /**
     * @param array{
     *   type_event:    string,
     *   galerie_id:    string,
     *   galerie_titre: string,
     *   client_email:  string,
     *   url_acces:     string,
     *   code_acces:    string,
     *   date_event:    string
     * } $event
     */
    public function handle(array $event): void;
}
