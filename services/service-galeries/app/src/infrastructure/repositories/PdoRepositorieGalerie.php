<?php
namespace photopro\galeries\infra\repositories;

use photopro\galeries\core\application\ports\repositories\GalerieRepositoryInterface;
use photopro\galeries\core\application\dto\GalerieDTO;
use photopro\galeries\core\domain\entities\Galerie;
use photopro\galeries\core\domain\entities\GaleriePhoto;
use Ramsey\Uuid\Uuid;
use PDO;

class PdoRepositorieGalerie implements GalerieRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(GalerieDTO $galerie): Galerie
    {
        Galerie::assertTypeIsValid($galerie->getType());
        Galerie::assertModeIsValid($galerie->getModeMiseEnPage());
        Galerie::assertStatutIsValid($galerie->getStatut());

        $id = Uuid::uuid4();
        $createdAt = $galerie->getCreatedAt();
        $publishedAt = $galerie->getPublishedAt();

        if ($galerie->getStatut() === Galerie::STATUT_PUBLIE && $publishedAt === null) {
            $publishedAt = new \DateTime();
        }

        $photoCouvertureId = null;
        if ($galerie->getPhotoCouvertureId() !== null && $galerie->getPhotoCouvertureId() !== '') {
            $photoCouvertureId = Uuid::fromString($galerie->getPhotoCouvertureId());
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO galerie (id, titre, description, type, mode_mise_en_page, statut, created_at, published_at, photographe_id, photo_couverture_id)
             VALUES (:id, :titre, :description, :type, :mode_mise_en_page, :statut, :created_at, :published_at, :photographe_id, :photo_couverture_id)'
        );

        $statement->execute([
            ':id' => $id->toString(),
            ':titre' => $galerie->getTitre(),
            ':description' => $galerie->getDescription() ?? '',
            ':type' => $galerie->getType(),
            ':mode_mise_en_page' => $galerie->getModeMiseEnPage(),
            ':statut' => $galerie->getStatut(),
            ':created_at' => $createdAt->format('Y-m-d H:i:s'),
            ':published_at' => $publishedAt?->format('Y-m-d H:i:s'),
            ':photographe_id' => $galerie->getPhotographeId(),
            ':photo_couverture_id' => $photoCouvertureId?->toString(),
        ]);

        return new Galerie(
            $id,
            $galerie->getTitre(),
            $galerie->getDescription(),
            $galerie->getType(),
            $galerie->getModeMiseEnPage(),
            $galerie->getStatut(),
            $createdAt,
            $publishedAt,
            Uuid::fromString($galerie->getPhotographeId()),
            $photoCouvertureId
        );
    }

    public function addPhotoToGalerie(GaleriePhoto $galeriePhoto): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO galerie_photo (galerie_id, photo_id, ordre, added_at)
             VALUES (:galerie_id, :photo_id, :ordre, :added_at)'
        );

        $statement->execute([
            ':galerie_id' => $galeriePhoto->getGalerieId()->toString(),
            ':photo_id' => $galeriePhoto->getPhotoId()->toString(),
            ':ordre' => $galeriePhoto->getOrdre(),
            ':added_at' => $galeriePhoto->getAddedAt()->format('Y-m-d H:i:s'),
        ]);
    }

    public function deletePhotoFromGalerie(string $photoId,string $galerieId): void
    {
        $statement = $this->pdo->prepare('DELETE FROM galerie_photo WHERE photo_id = :photo_id AND galerie_id = :galerie_id');
        $statement->execute([':photo_id' => $photoId, ':galerie_id' => $galerieId]);
    }

    public function getGalleryPreview(string $galleryId, string $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT 
                g.id AS galerie_id,
                g.titre,
                g.description,
                g.type,
                g.statut,
                g.mode_mise_en_page,
                g.created_at,
                g.published_at,
                p.id AS photo_id,
                p.url,
                p.titre AS photo_titre
            FROM galerie g
            LEFT JOIN galerie_photo gp ON g.id = gp.galerie_id
            LEFT JOIN photo p ON gp.photo_id = p.id
            WHERE g.id = :gallery_id 
            AND g.photographe_id = :user_id
            ORDER BY gp.ordre ASC'
        );

        $statement->execute([
            ':gallery_id' => $galleryId,
            ':user_id' => $userId
        ]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function publishGallery(string $galleryId, string $userId): void
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM galerie_photo WHERE galerie_id = :id'
        );
        $statement->execute([':id' => $galleryId]);
        $count = $statement->fetchColumn();

        if ($count == 0) {
            throw new \Exception("Impossible de publier une galerie vide");
        }

        $statement = $this->pdo->prepare(
            'UPDATE galerie 
            SET statut = :statut, published_at = NOW()
            WHERE id = :id AND photographe_id = :user_id'
        );

        $statement->execute([
            ':statut' => 'publie',
            ':id' => $galleryId,
            ':user_id' => $userId
        ]);

        if ($statement->rowCount() === 0) {
            throw new \Exception("Galerie non trouvée ou non autorisée");
        }
    }
}

