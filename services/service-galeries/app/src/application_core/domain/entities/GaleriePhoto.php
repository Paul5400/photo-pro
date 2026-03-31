<?php
namespace photopro\galeries\core\domain\entities;
use Ramsey\Uuid\Uuid;
use DateTime;
class   GaleriePhoto {
    
    private Uuid $galerie_id;
    private Uuid $photo_id;
    private int $ordre;
    private DateTime $added_at;

    public function __construct(
        Uuid $galerie_id,
        Uuid $photo_id,
        int $ordre,
        DateTime $added_at
    ) {
        $this->galerie_id = $galerie_id;
        $this->photo_id = $photo_id;
        $this->ordre = $ordre;
        $this->added_at = $added_at;
    }

    // Getters
    public function getGalerieId(): Uuid
    {
        return $this->galerie_id;
    }

    public function getPhotoId(): Uuid
    {
        return $this->photo_id;
    }

    public function getOrdre(): int
    {
        return $this->ordre;
    }

    public function getAddedAt(): DateTime
    {
        return $this->added_at;
    }
}
