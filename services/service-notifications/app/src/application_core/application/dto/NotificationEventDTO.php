<?php
declare(strict_types=1);

namespace photopro\notifications\core\application\dto;

use photopro\notifications\core\domain\value_objects\TypeEvenement;

/**
 * Transporte les données brutes du message AMQP vers le usecase.
 */
final class NotificationEventDTO
{
    public function __construct(
        public readonly TypeEvenement $typeEvenement,
        public readonly string        $galerieId,
        public readonly string        $galerieTitre,
        public readonly string        $clientEmail,
        public readonly string        $urlAcces,
        public readonly string        $codeAcces,
        public readonly \DateTimeImmutable $dateEvenement,
    ) {}

    // Construit le DTO depuis le tableau décodé du payload AMQP.
    public static function fromArray(array $data): self
    {
        $required = ['type_event', 'galerie_titre', 'client_email', 'url_acces', 'code_acces'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Champ obligatoire manquant : $field");
            }
        }

        return new self(
            typeEvenement:  TypeEvenement::fromString($data['type_event']),
            galerieId:      $data['galerie_id']    ?? '',
            galerieTitre:   $data['galerie_titre'],
            clientEmail:    $data['client_email'],
            urlAcces:       $data['url_acces'],
            codeAcces:      $data['code_acces'],
            dateEvenement:  new \DateTimeImmutable($data['date_event'] ?? 'now'),
        );
    }
}
