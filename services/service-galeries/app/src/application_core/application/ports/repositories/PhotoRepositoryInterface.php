<?php

namespace photopro\galeries\core\application\ports\repositories;

interface PhotoRepositoryInterface
{
    public function save(string $id, string $cheminS3, ?string $titre): void;

    public function findById(string $id): ?array;
}
