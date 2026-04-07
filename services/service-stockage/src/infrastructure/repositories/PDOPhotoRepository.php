<?php

namespace storage\infrastructure\repositories;

use storage\application_core\ports\PhotoRepositoryInterface;

class PDOPhotoRepository implements PhotoRepositoryInterface
{
    public function __construct(private \PDO $pdo) {}

    public function save(
        string $id,
        string $titre,
        string $mimeType,
        float  $tailleMo,
        string $nomFichierOriginal,
        string $cheminS3,
        string $photographeId
    ): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO photo (id, titre, mime_type, taille_mo, nom_fichier_original, chemin_s3, photographe_id)
             VALUES (:id, :titre, :mime_type, :taille_mo, :nom_fichier_original, :chemin_s3, :photographe_id)'
        );

        $stmt->execute([
            ':id'                   => $id,
            ':titre'                => $titre,
            ':mime_type'            => $mimeType,
            ':taille_mo'            => $tailleMo,
            ':nom_fichier_original' => $nomFichierOriginal,
            ':chemin_s3'            => $cheminS3,
            ':photographe_id'       => $photographeId,
        ]);
    }

    public function findCheminS3ById(string $id): ?string
    {
        $stmt = $this->pdo->prepare('SELECT chemin_s3 FROM photo WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? $row['chemin_s3'] : null;
    }

    public function findByPhotographeId(string $photographeId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, titre, mime_type, taille_mo, nom_fichier_original, chemin_s3, uploaded_at
             FROM photo
             WHERE photographe_id = :photographe_id
             ORDER BY uploaded_at DESC'
        );
        $stmt->execute([':photographe_id' => $photographeId]);
        return $stmt->fetchAll();
    }
}
