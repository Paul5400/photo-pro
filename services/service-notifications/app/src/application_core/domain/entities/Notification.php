<?php
declare(strict_types=1);

namespace photopro\notifications\core\domain\entities;

use photopro\notifications\core\domain\value_objects\TypeEvenement;

class Notification
{
    public function __construct(
        public readonly string        $galerieId,
        public readonly string        $galerieTitre,
        public readonly string        $clientEmail,
        public readonly string        $urlAcces,
        public readonly string        $codeAcces,
        public readonly TypeEvenement $typeEvenement,
        public readonly \DateTimeImmutable $dateEvenement,
        public readonly bool           $succes = false,
        public readonly ?string        $erreur = null,
    ) {}
}
