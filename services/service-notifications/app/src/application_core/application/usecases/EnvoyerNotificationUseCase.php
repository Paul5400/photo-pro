<?php
declare(strict_types=1);

namespace photopro\notifications\core\application\usecases;

use photopro\notifications\core\application\dto\NotificationEventDTO;
use photopro\notifications\core\application\ports\MailerInterface;
use photopro\notifications\core\application\ports\NotificationHandlerInterface;
use photopro\notifications\core\domain\value_objects\TypeEvenement;

/**
 * Usecase principal
 * validation du DTO -> envoi mail -> log.
 */
class EnvoyerNotificationUseCase implements NotificationHandlerInterface
{
    public function __construct(
        private readonly MailerInterface $mailer
    ) {}

    public function handle(array $event): void
    {
        // Validation et construction du DTO depuis le payload AMQP brut
        $dto = NotificationEventDTO::fromArray($event);

        // Seules les galeries privées génèrent des notifications
        if (empty($dto->clientEmail)) {
            echo "[UseCase] Pas de client email — notification ignorée.\n";
            return;
        }

        echo sprintf(
            "[UseCase] Envoi notification '%s' → %s\n",
            $dto->typeEvenement->value,
            $dto->clientEmail
        );

        $this->mailer->send($dto);

        echo "[UseCase] Mail(s) envoyé(s) avec succès.\n";
    }
}
