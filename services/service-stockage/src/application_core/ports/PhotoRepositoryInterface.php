<?php

namespace storage\application_core\ports;

interface PhotoRepositoryInterface
{
    public function save(
        string $id,
        string $titre,
        string $mimeType,
        float  $tailleMo,
        string $nomFichierOriginal,
        string $cheminS3,
        string $photographeId
    ): void;

    public function findCheminS3ById(string $id): ?string;

    public function findByPhotographeId(string $photographeId): array;
}
