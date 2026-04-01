<?php
declare(strict_types=1);

namespace photopro\notifications\core\domain\value_objects;

enum TypeEvenement: string
{
    case PUBLISHED   = 'gallery.published';
    case UNPUBLISHED = 'gallery.unpublished';
    case MODIFIED    = 'gallery.modified';

    public static function fromString(string $value): self
    {
        return self::from($value);
    }

    public function label(): string
    {
        return match($this) {
            self::PUBLISHED   => 'Publication',
            self::UNPUBLISHED => 'Dépublication',
            self::MODIFIED    => 'Modification',
        };
    }
}
