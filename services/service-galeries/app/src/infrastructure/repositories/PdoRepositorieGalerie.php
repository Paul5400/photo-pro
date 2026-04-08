<?php

namespace photopro\galeries\infra\repositories;

use photopro\galeries\core\application\ports\repositories\GalerieRepositoryInterface;
use photopro\galeries\core\application\dto\GalerieDTO;
use photopro\galeries\core\domain\entities\Galerie;
use photopro\galeries\core\domain\entities\GaleriePhoto;
use Ramsey\Uuid\Uuid;
use PDO;

/**
 * Implémentation PDO du repository galeries (PostgreSQL).
 *
 * Responsabilités :
 *   - CRUD galerie + galerie_privee
 *   - Gestion des associations galerie_photo
 *   - Requêtes de lecture (preview, liste, détail visiteur)
 *   - Publication / dépublication
 *
 * Toutes les méthodes lèvent une \Exception métier en cas d'erreur
 * (galerie introuvable, non autorisée, galerie vide, etc.).
 */
class PdoRepositorieGalerie implements GalerieRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Crée une galerie en base.  
     * Si le statut est "publie" et qu'aucune date de publication n'est fournie,
     * published_at est automatiquement fixé à maintenant.
     */
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
    /**
     * Crée l'enregistrement galerie_privee associé (code d'accès + URL d'accès client).
     * Le code est généré aléatoirement avec bin2hex(random_bytes(8)) (16 caractères hex).
     * L'URL est construite depuis la variable d'environnement FRONTOFFICE_URL.
     */
    public function createGaleriePrivee(
        string $galerieId,
        ?string $nomClient,
        ?string $emailClient,
        ?string $telephone
    ): string {
        $id = Uuid::uuid4();
        $code = bin2hex(random_bytes(8));
        $base = rtrim(getenv('FRONTOFFICE_URL') ?: 'http://localhost:8080', '/');
        $url = $base . '/galeries/' . $galerieId . '?code_acces=' . $code;

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

        return $code;
    }

    /**
     * Associe une photo à une galerie (INSERT dans galerie_photo).
     * L'ordre détermine la position d'affichage dans la galerie.
     */
    public function addPhotoToGalerie(GaleriePhoto $galeriePhoto): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO galerie_photo (galerie_id, photo_id, ordre, added_at, url)
             VALUES (:galerie_id, :photo_id, :ordre, :added_at, :url)'
        );

        $statement->execute([
            ':galerie_id' => $galeriePhoto->getGalerieId()->toString(),
            ':photo_id' => $galeriePhoto->getPhotoId()->toString(),
            ':ordre' => $galeriePhoto->getOrdre(),
            ':added_at' => $galeriePhoto->getAddedAt()->format('Y-m-d H:i:s'),
            ':url' => $galeriePhoto->getUrl(),
        ]);
    }

    /**
     * Supprime l'association entre une photo et une galerie.
     * Ne touche pas à la table photo ni au stockage S3.
     */
    public function deletePhotoFromGalerie(string $photoId, string $galerieId): void
    {
        $statement = $this->pdo->prepare('DELETE FROM galerie_photo WHERE photo_id = :photo_id AND galerie_id = :galerie_id');
        $statement->execute([':photo_id' => $photoId, ':galerie_id' => $galerieId]);
    }

    /**
     * Requête de prévisualisation : retourne toutes les lignes galerie + photos
     * pour un photographe donné. Pour les galeries privées, enrichit chaque ligne
     * avec les données client (nom, email, code_acces, url_acces).
     *
     * @return array Tableau de lignes associatives ; vide si non trouvée
     */
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
                gp.url AS url,
                NULL AS photo_titre
            FROM galerie g
            LEFT JOIN galerie_photo gp ON g.id::uuid = gp.galerie_id
            LEFT JOIN photo p ON gp.photo_id = p.id::uuid
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
            $privateStatement = $this->pdo->prepare(
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

    /**
     * Publie une galerie : passe le statut à "publie" et enregistre published_at = NOW().
     * Conditions :
     *   - La galerie doit contenir au moins une photo
     *   - photographe_id doit correspondre à $userId
     *
     * @throws \Exception "Impossible de publier une galerie vide"
     * @throws \Exception "Galerie non trouvée ou non autorisée"
     */
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

    /**
     * Dépublie une galerie : repasse le statut à "brouillon" et vide published_at.
     * photographe_id doit correspondre à $userId.
     *
     * @throws \Exception "Galerie non trouvée ou non autorisée"
     */
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

    /**
     * Charge une galerie publiée avec ses photos pour un visiteur (non authentifié).
     * Seules les galeries de statut "publie" sont retournées.
     * Pour les galeries privées, enrichit les lignes avec le code_acces
     * (la vérification du code est faite dans GetGalerieAction, pas ici).
     *
     * @return array Vide si la galerie n'existe pas ou n'est pas publiée
     */
    public function getGalleryForVisitor(string $galleryId): array
    {
        // Récupérer la galerie + ses photos (statut publie uniquement)
        $statement = $this->pdo->prepare(
            'SELECT
                g.id AS galerie_id,
                g.titre,
                g.description,
                g.type,
                g.statut,
                g.mode_mise_en_page,
                g.published_at,
                gp.photo_id,
                p.chemin_s3,
                p.titre AS photo_titre
            FROM galerie g
            LEFT JOIN galerie_photo gp ON g.id::uuid = gp.galerie_id
            LEFT JOIN photo p ON gp.photo_id = p.id::uuid
            WHERE g.id = :gallery_id
            AND g.statut = \'publie\'
            ORDER BY gp.ordre ASC'
        );

        $statement->execute([':gallery_id' => $galleryId]);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return [];
        }

        // Si galerie privée, récupérer le code_acces pour vérification dans l'action
        $type = strtolower(trim($rows[0]['type']));
        if (in_array($type, ['privée', 'privee', 'private'], true)) {
            $privStatement = $this->pdo->prepare(
                'SELECT code_acces FROM galerie_privee WHERE galerie_id = :gallery_id'
            );
            $privStatement->execute([':gallery_id' => $galleryId]);
            $privData = $privStatement->fetch(PDO::FETCH_ASSOC);

            foreach ($rows as &$row) {
                $row['code_acces'] = $privData['code_acces'] ?? null;
            }
            unset($row);
        }

        return $rows;
    }

    /**
     * Liste les galeries.
     *   - Avec $photographeId : toutes les galeries du photographe (brouillons inclus)
     *   - Sans $photographeId (null) : galeries publiques publiées uniquement
     *
     * @return array Tableau de lignes associatives
     */
    public function getGaleries(?string $photographeId): array
    {
        if ($photographeId !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT id, titre, description, type, statut, mode_mise_en_page,
                        created_at, published_at, photographe_id
                 FROM galerie
                 WHERE photographe_id = :photographe_id
                 ORDER BY created_at DESC'
            );
            $stmt->execute([':photographe_id' => $photographeId]);
        } else {
            $stmt = $this->pdo->prepare(
                "SELECT id, titre, description, type, statut, mode_mise_en_page,
                        created_at, published_at, photographe_id
                 FROM galerie
                 WHERE statut = 'publie' AND type = 'publique'
                 ORDER BY published_at DESC"
            );
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGalerieStatutEtType(string $galerieId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT statut, type FROM galerie WHERE id = :id');
        $stmt->execute([':id' => $galerieId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getCodeAcces(string $galerieId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT code_acces FROM galerie_privee WHERE galerie_id = :id');
        $stmt->execute([':id' => $galerieId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['code_acces'] : null;
    }

    public function addCommentaire(string $galerieId, string $photoId, string $auteur, string $contenu): string
    {
        $id = Uuid::uuid4()->toString();
        $stmt = $this->pdo->prepare(
            'INSERT INTO photo_commentaire (id, galerie_id, photo_id, auteur, contenu, created_at)
             VALUES (:id, :galerie_id, :photo_id, :auteur, :contenu, NOW())'
        );
        $stmt->execute([
            ':id'        => $id,
            ':galerie_id' => $galerieId,
            ':photo_id'  => $photoId,
            ':auteur'    => $auteur,
            ':contenu'   => $contenu,
        ]);
        return $id;
    }

    public function getCommentairesByPhoto(string $galerieId, string $photoId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, auteur, contenu, created_at 
             FROM photo_commentaire 
             WHERE galerie_id = :galerie_id AND photo_id = :photo_id 
             ORDER BY created_at ASC'
        );
        $stmt->execute([
            ':galerie_id' => $galerieId,
            ':photo_id'  => $photoId,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
