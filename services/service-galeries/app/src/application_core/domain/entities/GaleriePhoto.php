<?php
namespace photopro\galeries\core\domain\entities;
use Ramsey\Uuid\UuidInterface;
use DateTime;
class   GaleriePhoto {
    
    private UuidInterface $galerie_id;
    private UuidInterface $photo_id;
    private int $ordre;
    private DateTime $added_at;

    public function __construct(
        UuidInterface $galerie_id,
        UuidInterface $photo_id,
        int $ordre,
        DateTime $added_at
    ) {
        $this->galerie_id = $galerie_id;
        $this->photo_id = $photo_id;
        $this->ordre = $ordre;
        $this->added_at = $added_at;
    }

    // Getters
    public function getGalerieId(): UuidInterface
    {
        return $this->galerie_id;
    }

    public function getPhotoId(): UuidInterface
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
