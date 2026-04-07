<?php

namespace photopro\galeries\infra\repositories;

use photopro\galeries\core\application\ports\repositories\PhotoRepositoryInterface;

class PdoPhotoRepository implements PhotoRepositoryInterface
{
    public function __construct(private \PDO $pdo) {}

    public function save(string $id, string $cheminS3, ?string $titre): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO photo (id, chemin_s3, titre) VALUES (:id, :chemin_s3, :titre)'
        );
        $stmt->execute([
            ':id'        => $id,
            ':chemin_s3' => $cheminS3,
            ':titre'     => $titre,
        ]);
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, chemin_s3, titre FROM photo WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
