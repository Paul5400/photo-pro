<?php
namespace photopro\galeries\core\domain\entities;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use InvalidArgumentException;

class Galerie
{
    public const TYPE_PUBLIQUE = 'publique';
    public const TYPE_PRIVEE = 'privée';
    public const TYPES = [
        self::TYPE_PUBLIQUE,
        self::TYPE_PRIVEE,
    ];

    public const MODE_GRILLE = 'grille';
    public const MODE_MOSAÏQUE = 'mosaïque';
    public const MODE_CARROUSEL = 'carrousel';
    public const MODES = [
        self::MODE_GRILLE,
        self::MODE_MOSAÏQUE,
        self::MODE_CARROUSEL,
    ];

    public const STATUT_BROUILLON = 'brouillon';
    public const STATUT_PUBLIE = 'publie';
    public const STATUTS = [
        self::STATUT_BROUILLON,
        self::STATUT_PUBLIE,
    ];

    private UuidInterface $id;
    private string $titre;
    private ?string $description;
    private string $type;
    private string $mode_mise_en_page;
    private string $statut;
    private \datetime $created_at;
    private ?\datetime $published_at;
    private UuidInterface $photographe_id;
    private ?UuidInterface $photo_couverture_id;

    public function __construct(
        UuidInterface|Uuid $id,
        string $titre,
        ?string $description,
        string $type,
        string $mode_mise_en_page,
        string $statut,
        \datetime $created_at,
        ?\datetime $published_at,
        UuidInterface|Uuid $photographe_id,
        UuidInterface|Uuid|null $photo_couverture_id
    ) {
        self::assertTypeIsValid($type);
        self::assertModeIsValid($mode_mise_en_page);
        self::assertStatutIsValid($statut);

        $this->id = $id;
        $this->titre = $titre;
        $this->description = $description ?? '';
        $this->type = $type;
        $this->mode_mise_en_page = $mode_mise_en_page;
        $this->statut = $statut;
        $this->created_at = $created_at;
        $this->published_at = $published_at;
        $this->photographe_id = $photographe_id;
        $this->photo_couverture_id = $photo_couverture_id;
    }

    public static function assertTypeIsValid(string $type): void
    {
        if (!in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException(sprintf('Type de galerie invalide : %s.', $type));
        }
    }

    public static function assertModeIsValid(string $mode): void
    {
        if (!in_array($mode, self::MODES, true)) {
            throw new InvalidArgumentException(sprintf('Mode de mise en page invalide : %s.', $mode));
        }
    }

    public static function assertStatutIsValid(string $statut): void
    {
        if (!in_array($statut, self::STATUTS, true)) {
            throw new InvalidArgumentException(sprintf('Statut de galerie invalide : %s.', $statut));
        }
    }

    // Getters
    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getModeMiseEnPage(): string
    {
        return $this->mode_mise_en_page;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function getCreatedAt(): \datetime
    {
        return $this->created_at;
    }

    public function getPublishedAt(): ?\datetime
    {
        return $this->published_at;
    }

    public function getPhotographeId(): UuidInterface
    {
        return $this->photographe_id;
    }

    public function getPhotoCouvertureId(): ?UuidInterface
    {
        return $this->photo_couverture_id;
    }
}
