<?php
namespace photopro\galeries\core\application\dto;
use Ramsey\Uuid\Uuid;

/**
 * Data Transfer Object utilisé pour créer une galerie.
 *
 * Transporte les données entre la couche HTTP (action) et le service applicatif.
 * Les champs nomClient / emailClient / telephoneClient ne sont utilisés
 * que pour les galeries de type "privée".
 */
class GalerieDTO
{
    public string $titre;
    public ?string $description;
    public string $type;
    public string $mode_mise_en_page;
    public string $statut;
    public \datetime $created_at;
    public ?\datetime $published_at;
    public string $photographe_id;
    public ?string $photo_couverture_id;
    private ?string $nomClient;
    private ?string $emailClient;
    private ?string $telephoneClient;

    public function __construct(
        string $titre,
        string $type,
        string $mode_mise_en_page,
        string $statut,
        \datetime $created_at,
        string $photographe_id,
        ?string $description = null,
        ?string $photo_couverture_id = null,
        ?\datetime $published_at = null,
       ?string $nomClient = null,
        ?string $emailClient = null,
        ?string $telephoneClient = null
    ) {
        $this->titre = $titre;
        $this->description = $description;
        $this->type = $type;
        $this->mode_mise_en_page = $mode_mise_en_page;
        $this->statut = $statut;
        $this->created_at = $created_at;
        $this->published_at = $published_at;
        $this->photographe_id = $photographe_id;
        $this->photo_couverture_id = $photo_couverture_id;
        $this->nomClient = $nomClient;
        $this->emailClient = $emailClient;
        $this->telephoneClient = $telephoneClient;
    }

    // Getters
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

    public function getPhotographeId(): string
    {
        return $this->photographe_id;
    }

    public function getPhotoCouvertureId(): ?string
    {
        return $this->photo_couverture_id;
    }
    public function getNomClient(): ?string
    {
        return $this->nomClient;
    }

    public function getEmailClient(): ?string
    {
        return $this->emailClient;
    }

    public function getTelephoneClient(): ?string
    {
        return $this->telephoneClient;
    }
}
