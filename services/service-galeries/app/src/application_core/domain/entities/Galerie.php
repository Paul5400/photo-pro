<?php
namespace photopro\galeries\core\domain\entities;
use Ramsey\Uuid\Uuid;
class Galerie
{
    private Uuid $id;
    private string $titre;
    private string $description;
    private string $type;
    private string $mode_mise_en_page;
    private string $statut;
    private \datetime $created_at;
    private \datetime $published_at;
    private Uuid $photographe_id;
    private Uuid $photo_couverture_id;
    public function __construct(
        Uuid $id,
        string $titre,
        string $description,
        string $type,
        string $mode_mise_en_page,
        string $statut,
        \datetime $created_at,
        \datetime $published_at,
        Uuid $photographe_id,
        Uuid $photo_couverture_id
    ) {
        $this->id = $id;
        $this->titre = $titre;
        $this->description = $description;
        $this->type = $type;
        $this->mode_mise_en_page = $mode_mise_en_page;
        $this->statut = $statut;
        $this->created_at = $created_at;
        $this->published_at = $published_at;
        $this->photographe_id = $photographe_id;
        $this->photo_couverture_id = $photo_couverture_id;
    }
    // Getters
    public function getId(): Uuid
    {
        return $this->id;
    }
    public function getTitre(): string
    {
        return $this->titre;
    }
    public function getDescription(): string
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
    public function getPublishedAt(): \datetime
    {
        return $this->published_at;
    }
    public function getPhotographeId(): Uuid
    {
        return $this->photographe_id;
    }
    public function getPhotoCouvertureId(): Uuid
    {
        return $this->photo_couverture_id;
    }
    

}