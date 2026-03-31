<?php
namespace  photopro\galeries\core\application\dto;
use Ramsey\Uuid\Uuid;

class GalerieDTO
{
    public string $titre;
    public string $type;
    public string $mode_mise_en_page;
    public string $statut;
    public \datetime $created_at;
    public string $photographe_id;

    public function __construct(
        string $titre,
        string $type,
        string $mode_mise_en_page,
        string $statut,
        \datetime $created_at,
        string $photographe_id,
    ) {
        $this->titre = $titre;
        $this->type = $type;
        $this->mode_mise_en_page = $mode_mise_en_page;
        $this->statut = $statut;
        $this->created_at = $created_at;
        $this->photographe_id = $photographe_id;
    }
    // Getters
    public function getTitre(): string
    {
        return $this->titre;
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
    public function getPhotographeId(): string
    {
        return $this->photographe_id;
    }
    
}