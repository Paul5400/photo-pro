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
            $photoCouvertureId,
        );
    }
    public function createGaleriePrivee(
        string $galerieId,
        string $nomClient,
        string $emailClient,
        ?string $telephone):void{
        $id = Uuid::uuid4();
        $code = bin2hex(random_bytes(8));
        $url = "https://site.com/galerie/".$code;

        $stmt = $this->pdo->prepare(
            'INSERT INTO galerie_privee
            (id, galerie_id, nom_client, email_client, telephone_client, code_acces, url_acces)
            VALUES
            (:id, :galerie_id, :nom_client, :email_client, :telephone_client, :code_acces, :url_acces)'
        );

        $stmt->execute([
            ':id' => $id->toString(),
            ':galerie_id' => $galerieId,
            ':nom_client' => $nomClient,
            ':email_client' => $emailClient,
            ':telephone_client' => $telephone,
            ':code_acces' => $code,
            ':url_acces' => $url
        ]);
    }

    public function addPhotoToGalerie(GaleriePhoto $galeriePhoto, string $photographeId): void
    {
        $check = $this->pdo->prepare(
            'SELECT COUNT(*) FROM galerie WHERE id = :galerie_id AND photographe_id = :photographe_id'
        );
        $check->execute([
            ':galerie_id'     => $galeriePhoto->getGalerieId()->toString(),
            ':photographe_id' => $photographeId,
        ]);
        if ((int) $check->fetchColumn() === 0) {
            throw new \Exception("Galerie non trouvée ou non autorisée");
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO galerie_photo (galerie_id, photo_id, ordre, added_at)
             VALUES (:galerie_id, :photo_id, :ordre, :added_at)'
        );

        $statement->execute([
            ':galerie_id' => $galeriePhoto->getGalerieId()->toString(),
            ':photo_id'   => $galeriePhoto->getPhotoId()->toString(),
            ':ordre'      => $galeriePhoto->getOrdre(),
            ':added_at'   => $galeriePhoto->getAddedAt()->format('Y-m-d H:i:s'),
        ]);
    }

    public function deletePhotoFromGalerie(string $photoId, string $galerieId, string $photographeId): void
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM galerie_photo
             WHERE photo_id = :photo_id
             AND galerie_id = :galerie_id
             AND EXISTS (
                 SELECT 1 FROM galerie
                 WHERE id = :galerie_check AND photographe_id = :photographe_id
             )'
        );
        $statement->execute([
            ':photo_id'       => $photoId,
            ':galerie_id'     => $galerieId,
            ':galerie_check'  => $galerieId,
            ':photographe_id' => $photographeId,
        ]);
        if ($statement->rowCount() === 0) {
            throw new \Exception("Photo non trouvée dans la galerie ou non autorisé");
        }
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
                gp.photo_id,
                NULL AS url,
                NULL AS photo_titre
            FROM galerie g
            LEFT JOIN galerie_photo gp ON g.id::uuid = gp.galerie_id
            WHERE g.id = :gallery_id 
            AND g.photographe_id = :user_id
            ORDER BY gp.ordre ASC'
        );

        $statement->execute([
            ':gallery_id' => $galleryId,
            ':user_id' => $userId
        ]);

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return [];
        }

        $type = strtolower(trim($rows[0]['type']));
        if (in_array($type, ['privée', 'privee', 'private'], true)) {
            $privateStatement = $this->pdoGaleriePrivee->prepare(
                'SELECT nom_client, email_client, telephone_client, code_acces, url_acces
                 FROM galerie_privee
                 WHERE galerie_id = :gallery_id'
            );
            $privateStatement->execute([':gallery_id' => $galleryId]);
            $privateData = $privateStatement->fetch(PDO::FETCH_ASSOC) ?: [];

            foreach ($rows as &$row) {
                $row['nom_client'] = $privateData['nom_client'] ?? null;
                $row['email_client'] = $privateData['email_client'] ?? null;
                $row['telephone_client'] = $privateData['telephone_client'] ?? null;
                $row['code_acces'] = $privateData['code_acces'] ?? null;
                $row['url_acces'] = $privateData['url_acces'] ?? null;
            }
            unset($row);
        }

        return $rows;
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

    public function unpublishGallery(string $galleryId, string $userId): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE galerie
            SET statut = :statut, published_at = NULL
            WHERE id = :id AND photographe_id = :user_id'
        );

        $statement->execute([
            ':statut' => 'brouillon',
            ':id' => $galleryId,
            ':user_id' => $userId
        ]);

        if ($statement->rowCount() === 0) {
            throw new \Exception("Galerie non trouvée ou non autorisée");
        }
    }

    public function getGalerieForNotification(string $galleryId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT g.titre, gp.email_client, gp.url_acces, gp.code_acces
             FROM galerie g
             LEFT JOIN galerie_privee gp ON gp.galerie_id = g.id
             WHERE g.id = :gallery_id'
        );
        $statement->execute([':gallery_id' => $galleryId]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return $row ?: ['titre' => '', 'email_client' => null, 'url_acces' => null, 'code_acces' => null];
    }

    public function getGalerieForComment(string $galerieId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT g.statut, g.type, gp.code_acces
             FROM galerie g
             LEFT JOIN galerie_privee gp ON gp.galerie_id = g.id
             WHERE g.id = :galerie_id'
        );
        $statement->execute([':galerie_id' => $galerieId]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function isPhotoInGalerie(string $galerieId, string $photoId): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM galerie_photo WHERE galerie_id = :galerie_id AND photo_id = :photo_id'
        );
        $statement->execute([':galerie_id' => $galerieId, ':photo_id' => $photoId]);

        return (int) $statement->fetchColumn() > 0;
    }

    public function addCommentaire(string $galerieId, string $photoId, ?string $auteur, string $contenu): string
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO photo_commentaire (galerie_id, photo_id, auteur, contenu)
             VALUES (:galerie_id, :photo_id, :auteur, :contenu)
             RETURNING id'
        );
        $statement->execute([
            ':galerie_id' => $galerieId,
            ':photo_id'   => $photoId,
            ':auteur'     => $auteur,
            ':contenu'    => $contenu,
        ]);

        return (string) $statement->fetchColumn();
    }

    public function findByPhotographe(string $photographeId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT g.id, g.titre, g.description, g.type, g.mode_mise_en_page, g.statut,
                    g.created_at, g.published_at,
                    COUNT(gp.photo_id) AS nb_photos
             FROM galerie g
             LEFT JOIN galerie_photo gp ON g.id::uuid = gp.galerie_id
             WHERE g.photographe_id = :photographe_id
             GROUP BY g.id
             ORDER BY g.created_at DESC'
        );
        $statement->execute([':photographe_id' => $photographeId]);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findPublishedById(string $galerieId, ?string $codeAcces): array
    {
        $statement = $this->pdo->prepare(
            'SELECT g.id, g.titre, g.description, g.type, g.mode_mise_en_page,
                    g.statut, g.created_at, g.published_at, g.photographe_id,
                    gpriv.code_acces, gpriv.url_acces,
                    gp.photo_id, gp.ordre
             FROM galerie g
             LEFT JOIN galerie_privee gpriv ON gpriv.galerie_id = g.id
             LEFT JOIN galerie_photo gp ON g.id::uuid = gp.galerie_id
             WHERE g.id = :galerie_id AND g.statut = \'publie\'
             ORDER BY gp.ordre ASC'
        );
        $statement->execute([':galerie_id' => $galerieId]);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            throw new \RuntimeException('Galerie non trouvée ou non publiée');
        }

        $first = $rows[0];
        $type  = strtolower(trim($first['type'] ?? ''));

        if (in_array($type, ['privée', 'privee', 'private'], true)) {
            if (empty($codeAcces) || $first['code_acces'] !== $codeAcces) {
                throw new \RuntimeException("Code d'accès invalide");
            }
        }

        $galerie = [
            'id'               => $first['id'],
            'titre'            => $first['titre'],
            'description'      => $first['description'],
            'type'             => $first['type'],
            'mode_mise_en_page'=> $first['mode_mise_en_page'],
            'statut'           => $first['statut'],
            'created_at'       => $first['created_at'],
            'published_at'     => $first['published_at'],
            'photographe_id'   => $first['photographe_id'],
        ];

        $photos = array_values(
            array_filter(
                array_map(fn($r) => $r['photo_id'] ? ['photo_id' => $r['photo_id'], 'ordre' => (int) $r['ordre']] : null, $rows)
            )
        );

        return ['galerie' => $galerie, 'photos' => $photos];
    }
}

